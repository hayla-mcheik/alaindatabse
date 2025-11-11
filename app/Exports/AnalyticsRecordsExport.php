<?php

namespace App\Exports;

use App\Models\AnalyticsRecord;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AnalyticsRecordsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

public function collection()
{
    $query = AnalyticsRecord::query();

    // Apply the same filters as in the index method
    if (isset($this->filters['platform']) && $this->filters['platform']) {
        $query->where('platform', $this->filters['platform']);
    }

    // Update country filter to support multiple countries
    if (isset($this->filters['countries']) && $this->filters['countries']) {
        $selectedCountries = is_array($this->filters['countries']) ? $this->filters['countries'] : [$this->filters['countries']];
        $query->whereIn('country', $selectedCountries);
    }

    // Keep the old single country filter for backward compatibility
    if (isset($this->filters['country']) && $this->filters['country'] && !isset($this->filters['countries'])) {
        $query->where('country', 'like', '%' . $this->filters['country'] . '%');
    }

    // ... rest of the existing filters remain the same
    if (isset($this->filters['client']) && $this->filters['client']) {
        $query->where('client', 'like', '%' . $this->filters['client'] . '%');
    }

    if (isset($this->filters['agency']) && $this->filters['agency']) {
        if ($this->filters['agency'] === 'direct') {
            $query->where(function($q) {
                $q->whereNull('agency')
                  ->orWhere('agency', '')
                  ->orWhere('agency', 'Unknown Agency')
                  ->orWhere('agency', 'direct');
            });
        } else {
            $query->where('agency', 'like', '%' . $this->filters['agency'] . '%');
        }
    }

    if (isset($this->filters['search']) && $this->filters['search']) {
        $query->where(function ($q) {
            $q->where('client', 'like', '%' . $this->filters['search'] . '%')
              ->orWhere('agency', 'like', '%' . $this->filters['search'] . '%')
              ->orWhere('country', 'like', '%' . $this->filters['search'] . '%');
        });
    }

    // Budget tier filter
    if (isset($this->filters['budget_tier']) && $this->filters['budget_tier']) {
        $budgetStats = AnalyticsRecord::selectRaw('MIN(budget) as min_budget, MAX(budget) as max_budget, AVG(budget) as avg_budget')->first();
        
        $avgBudget = $budgetStats->avg_budget ?? 0;

        switch ($this->filters['budget_tier']) {
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

    // Budget range filter
    if (isset($this->filters['min_budget']) && $this->filters['min_budget']) {
        $query->where('budget', '>=', $this->filters['min_budget']);
    }

    if (isset($this->filters['max_budget']) && $this->filters['max_budget']) {
        $query->where('budget', '<=', $this->filters['max_budget']);
    }

    return $query->latest()->get();
}

    public function headings(): array
    {
        return [
            'ID',
            'Client',
            'Agency',
            'Budget',
            'Platform',
            'Country',
            'Source File',
            'Created At',
            'Updated At'
        ];
    }

    public function map($record): array
    {
        return [
            $record->id,
            $record->client,
            $record->agency === 'direct' ? 'Direct' : $record->agency,
            '$' . number_format($record->budget, 2),
            ucfirst($record->platform),
            $record->country,
            $record->source_file,
            $record->created_at->format('Y-m-d H:i:s'),
            $record->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
            
            // Style the header row
            'A1:I1' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['argb' => 'FFE6E6FA']
                ]
            ],
        ];
    }
}