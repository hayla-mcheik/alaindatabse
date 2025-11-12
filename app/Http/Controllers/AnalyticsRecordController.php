<?php

namespace App\Http\Controllers;

use App\Models\AnalyticsRecord;
use App\Imports\AnalyticsRecordImport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use App\Exports\AnalyticsRecordsExport;
class AnalyticsRecordController extends Controller
{

public function index(Request $request)
{
    $query = AnalyticsRecord::query();

    // Apply filters (your existing filter code remains the same)
    if ($request->has('platform') && $request->platform) {
        $query->where('platform', $request->platform);
    }

    // Update country filter to support multiple countries
    if ($request->has('countries') && $request->countries) {
        $selectedCountries = is_array($request->countries) ? $request->countries : [$request->countries];
        $query->whereIn('country', $selectedCountries);
    }

    // Keep the old single country filter for backward compatibility
    if ($request->has('country') && $request->country && !$request->has('countries')) {
        $query->where('country', 'like', '%' . $request->country . '%');
    }

    if ($request->has('client') && $request->client) {
        $query->where('client', 'like', '%' . $request->client . '%');
    }

    if ($request->has('agency') && $request->agency) {
        if ($request->agency === 'direct') {
            $query->where(function($q) {
                $q->whereNull('agency')
                  ->orWhere('agency', '')
                  ->orWhere('agency', 'Unknown Agency')
                  ->orWhere('agency', 'direct');
            });
        } else {
            $query->where('agency', 'like', '%' . $request->agency . '%');
        }
    }

    if ($request->has('search') && $request->search) {
        $query->where(function ($q) use ($request) {
            $q->where('client', 'like', '%' . $request->search . '%')
              ->orWhere('agency', 'like', '%' . $request->search . '%')
              ->orWhere('country', 'like', '%' . $request->search . '%');
        });
    }

    // Budget tier filter
    if ($request->has('budget_tier') && $request->budget_tier) {
        $budgetStats = AnalyticsRecord::selectRaw('MIN(budget) as min_budget, MAX(budget) as max_budget, AVG(budget) as avg_budget')->first();
        
        $minBudget = $budgetStats->min_budget ?? 0;
        $maxBudget = $budgetStats->max_budget ?? 0;
        $avgBudget = $budgetStats->avg_budget ?? 0;

        switch ($request->budget_tier) {
            case 'top':
                $query->where('budget', '>', $avgBudget * 1.5);
                break;
            case 'mid':
                $query->whereBetween('budget', [$avgBudget * 0.5, $avgBudget * 1.5]);
                break;
            case 'bottom':
                $query->where('budget', '<', $avgBudget * 0.5);
                break;
            case 'high':
                $query->where('budget', '>', 10000);
                break;
            case 'medium':
                $query->whereBetween('budget', [1000, 10000]);
                break;
            case 'low':
                $query->where('budget', '<', 1000);
                break;
        }
    }

    // Budget range filter (keep existing)
    if ($request->has('min_budget') && $request->min_budget) {
        $query->where('budget', '>=', $request->min_budget);
    }

    if ($request->has('max_budget') && $request->max_budget) {
        $query->where('budget', '<=', $request->max_budget);
    }

    // Apply sorting - NEW CODE
    $sortField = $request->get('sort', 'budget'); // Default to budget
    $sortDirection = $request->get('direction', 'desc'); // Default to descending (highest first)

    // Validate sort field to prevent SQL injection
    $allowedSortFields = ['budget', 'client', 'agency', 'platform', 'country', 'created_at'];
    if (!in_array($sortField, $allowedSortFields)) {
        $sortField = 'budget';
    }

    // Validate direction
    $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? $sortDirection : 'desc';

    $query->orderBy($sortField, $sortDirection);

    // Change pagination from 15 to 50 rows per page
    $records = $query->paginate(50);

    // Get filter options with "Direct" option for agencies
    $platforms = AnalyticsRecord::distinct()->pluck('platform');
    $countries = AnalyticsRecord::distinct()->pluck('country');
    $clients = AnalyticsRecord::distinct()->pluck('client');
    
    // Get agencies and add "Direct" option for null/empty agencies
    $agencies = AnalyticsRecord::whereNotNull('agency')
        ->where('agency', '!=', '')
        ->where('agency', '!=', 'Unknown Agency')
        ->distinct()
        ->pluck('agency');

    return Inertia::render('Analytics/Index', [
        'records' => $records,
        'platforms' => $platforms,
        'countries' => $countries,
        'clients' => $clients,
        'agencies' => $agencies,
        'filters' => $request->only([
            'search', 
            'platform', 
            'country', 
            'countries',
            'client', 
            'agency',
            'budget_tier',
            'min_budget',
            'max_budget'
        ]),
        'sort' => $sortField,
        'direction' => $sortDirection
    ]);
}
public function import(Request $request)
{
    \Log::info('Import request received', ['files_count' => count($request->file('files') ?? [])]);

    $request->validate([
        'files.*' => 'required|file|mimes:xlsx,xls,csv|max:10240'
    ]);

    $importedFiles = [];
    $importedCount = 0;
    $errors = [];

    foreach ($request->file('files') as $file) {
        try {
            $filename = $file->getClientOriginalName();
            \Log::info("Importing file: {$filename}");
            
            $import = new AnalyticsRecordImport($filename);
            Excel::import($import, $file);
            $importedFiles[] = $filename;
            $rowCount = $import->getRowCount();
            $importedCount += $rowCount;

            \Log::info("File {$filename} imported successfully. Rows: {$rowCount}");

            // Get any failures that occurred during import
            $failures = $import->failures();
            if ($failures->count() > 0) {
                foreach ($failures as $failure) {
                    $errors[] = [
                        'file' => $filename,
                        'row' => $failure->row(),
                        'attribute' => $failure->attribute(),
                        'errors' => $failure->errors(),
                        'values' => $failure->values()
                    ];
                }
                \Log::warning("Import had failures for {$filename}", ['failures' => $failures->count()]);
            }

        } catch (ValidationException $e) {
            $failures = $e->failures();
            
            foreach ($failures as $failure) {
                $errors[] = [
                    'file' => $filename,
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                    'values' => $failure->values()
                ];
            }
            
            \Log::error("Validation error importing {$filename}", ['failures' => $failures->count()]);
            
        } catch (\Exception $e) {
            \Log::error('Import error for file ' . $filename . ': ' . $e->getMessage());
            $errors[] = [
                'file' => $filename,
                'row' => 'N/A',
                'attribute' => 'General',
                'errors' => [$e->getMessage()],
                'values' => []
            ];
        }
    }

    // Prepare response message
    if ($importedCount > 0) {
        $message = "Successfully imported {$importedCount} records from: " . implode(', ', $importedFiles);
    } else {
        $message = "No records were imported.";
    }

    // Add errors to session if any
    if (!empty($errors)) {
        $errorCount = count($errors);
        $message .= " Found {$errorCount} error(s).";
        
        // Store errors in session for display
        session()->flash('import_errors', $errors);
    }

    \Log::info('Import completed: ' . $message);
    
    if ($importedCount > 0) {
        return redirect()->back()->with('success', $message);
    } else {
        return redirect()->back()->with('error', $message);
    }
}
    public function destroy(AnalyticsRecord $record)
    {
        $record->delete();
        return redirect()->back()->with('success', 'Record deleted successfully.');
    }

    public function clearAll()
    {
        AnalyticsRecord::truncate();
        return redirect()->back()->with('success', 'All records cleared successfully.');
    }

    public function stats()
    {
        $stats = [
            'total_records' => AnalyticsRecord::count(),
            'total_budget' => AnalyticsRecord::sum('budget'),
            'platform_stats' => AnalyticsRecord::selectRaw('platform, COUNT(*) as count, SUM(budget) as total_budget')
                ->groupBy('platform')
                ->get(),
            'top_clients' => AnalyticsRecord::selectRaw('client, COUNT(*) as count, SUM(budget) as total_budget')
                ->groupBy('client')
                ->orderBy('total_budget', 'desc')
                ->limit(5)
                ->get(),
        ];

        return Inertia::render('Analytics/Stats', ['stats' => $stats]);
    }



public function export(Request $request)
{
    $filters = $request->only([
        'search', 
        'platform', 
        'country', 
        'countries', // Add countries to export filters
        'client', 
        'agency',
        'budget_tier',
        'min_budget',
        'max_budget'
    ]);

    $export = new AnalyticsRecordsExport($filters);
    
    $fileName = 'analytics-records-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
    
    return Excel::download($export, $fileName);
}

}