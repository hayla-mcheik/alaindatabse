<?php

namespace App\Imports;

use App\Models\AnalyticsRecord;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Carbon\Carbon;

class AnalyticsRecordImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnFailure
{
    use SkipsFailures;

    private $sourceFile;
    private $successfulRows = 0;
    private $isExportFile = false;

    public function __construct($sourceFile)
    {
        $this->sourceFile = $sourceFile;
        $this->isExportFile = $this->isExportFileFormat($sourceFile);
    }

    public function model(array $row)
    {
        \Log::info('Processing row:', $row);

        // Skip empty rows
        if ($this->isEmptyRow($row)) {
            \Log::info('Skipping empty row');
            return null;
        }

        try {
            // Extract data based on file type
            if ($this->isExportFile) {
                // Handle export file format (your system's export structure)
                $data = $this->extractFromExportFile($row);
            } else {
                // Handle platform file format (Google, TikTok, Snap, etc.)
                $data = $this->extractFromPlatformFile($row);
            }

            \Log::info('Extracted data:', $data);

            // Skip if essential data is missing
            if (empty($data['client']) || $data['client'] === 'Unknown Client') {
                \Log::warning('Skipping row: Missing client data');
                return null;
            }

            // Handle negative budgets by setting to 0.01
            if ($data['budget'] < 0) {
                \Log::warning('Negative budget found, setting to 0.01', [
                    'client' => $data['client'],
                    'original_budget' => $data['budget'],
                    'new_budget' => 0.01
                ]);
                $data['budget'] = 0.01;
            }

            if ($data['budget'] <= 0) {
                \Log::warning('Skipping row: Invalid budget', ['budget' => $data['budget']]);
                return null;
            }

            $this->successfulRows++;

            $record = new AnalyticsRecord([
                'client' => $data['client'],
                'agency' => $data['agency'],
                'budget' => $data['budget'],
                'platform' => $data['platform'],
                'country' => $data['country'],
                'date' => $data['date'],
                'source_file' => $this->sourceFile,
            ]);

            \Log::info('Created record:', $record->toArray());
            return $record;

        } catch (\Exception $e) {
            \Log::error('Error creating record: ' . $e->getMessage());
            \Log::error('Row data that caused error:', $row);
            return null;
        }
    }

    private function isExportFileFormat($filename)
    {
        // Check if this is an export file from our system
        return str_contains($filename, 'analytics-records-') || 
               str_contains($filename, 'export') ||
               str_contains($filename, 'backup');
    }

    private function isEmptyRow($row)
    {
        // Check if row is essentially empty
        $nonEmptyValues = array_filter($row, function($value) {
            return !is_null($value) && $value !== '' && $value !== ' ' && $value !== 'NULL';
        });
        return empty($nonEmptyValues);
    }

    private function extractFromExportFile(array $row)
    {
        // For export files (your system's export format)
        return [
            'client' => $this->getValue($row, ['client', 'client_name'], 'Unknown Client'),
            'agency' => $this->normalizeAgency($this->getValue($row, ['agency', 'agency_name'], 'direct')),
            'budget' => $this->normalizeBudget($this->getValue($row, ['budget', 'budget_amount'], 0)),
            'platform' => $this->normalizePlatform($this->getValue($row, ['platform', 'platform_name'], $this->determinePlatformFromFilename($this->sourceFile))),
            'country' => $this->getValue($row, ['country', 'country_name'], 'Unknown'),
            'date' => $this->extractDate($row), // Fixed: Use extractDate method
        ];
    }

    private function extractFromPlatformFile(array $row)
    {
        // For platform files (Google, TikTok, Snap, etc.)
        $platformFromFile = $this->determinePlatformFromFilename($this->sourceFile);
        
        return [
            'client' => $this->extractClient($row),
            'agency' => $this->extractAgency($row),
            'budget' => $this->extractBudget($row),
            'platform' => $this->extractPlatform($row) ?: $platformFromFile,
            'country' => $this->extractCountry($row),
            'date' => $this->extractDate($row), // Fixed: Use extractDate method instead of extractCountry
        ];
    }

    // Add this method to extract date
    private function extractDate(array $row)
    {
        $possibleKeys = [
            'date', 'transaction_date', 'report_date', 'period', 
            'month', 'year', 'time_period', 'date_range'
        ];
        
        $dateValue = $this->getValue($row, $possibleKeys, null);
        return $this->normalizeDate($dateValue);
    }

    // Add this method to normalize date
    private function normalizeDate($dateValue)
    {
        if (is_null($dateValue) || $dateValue === '' || $dateValue === 'NULL') {
            return null;
        }

        try {
            // Handle numeric years like 2025
            if (is_numeric($dateValue)) {
                $year = intval($dateValue);
                // If it's a reasonable year (between 2000 and current year + 1)
                if ($year >= 2000 && $year <= (date('Y') + 1)) {
                    return Carbon::createFromDate($year, 1, 1); // Default to Jan 1 of that year
                }
            }

            // Handle Excel serial date numbers (like 44927)
            if (is_numeric($dateValue) && $dateValue > 1000) {
                return Carbon::createFromDate(1900, 1, 1)->addDays(intval($dateValue) - 2);
            }

            // Handle string dates
            if (is_string($dateValue)) {
                // Try different date formats
                $formats = [
                    'Y-m-d',
                    'Y/m/d',
                    'd-m-Y',
                    'd/m/Y',
                    'm-d-Y',
                    'm/d/Y',
                    'Y',
                    'Y-m',
                ];

                foreach ($formats as $format) {
                    $date = Carbon::createFromFormat($format, $dateValue);
                    if ($date !== false) {
                        return $date;
                    }
                }

                // If none of the formats work, try Carbon's parser
                return Carbon::parse($dateValue);
            }

            return null;
        } catch (\Exception $e) {
            \Log::warning("Could not parse date: {$dateValue}. Error: " . $e->getMessage());
            return null;
        }
    }

    private function determinePlatformFromFilename($filename)
    {
        $filename = strtolower($filename);
        
        if (str_contains($filename, 'tiktok') || str_contains($filename, 'tik-tok')) {
            return 'tiktok';
        }
        if (str_contains($filename, 'google')) {
            return 'google';
        }
        if (str_contains($filename, 'snap') || str_contains($filename, 'snapchat')) {
            return 'snap';
        }
        if (str_contains($filename, 'meta') || str_contains($filename, 'facebook') || str_contains($filename, 'instagram')) {
            return 'meta';
        }
        if (str_contains($filename, 'twitter') || str_contains($filename, 'x')) {
            return 'twitter';
        }
        
        return 'google'; // default
    }

    private function extractClient(array $row)
    {
        $possibleKeys = [
            'client', 'client_name', 'account_name', 'account', 
            'advertiser', 'advertiser_name', 'company', 'customer',
            'ad_account_name', 'advertiser_name'
        ];
        
        return $this->getValue($row, $possibleKeys, 'Unknown Client');
    }

    private function extractAgency(array $row)
    {
        $possibleKeys = [
            'agency', 'agency_name', 'partner', 'manager', 
            'account_manager', 'partner_name', 'management_company'
        ];
        
        $agency = $this->getValue($row, $possibleKeys, 'direct');
        return $this->normalizeAgency($agency);
    }

    private function extractBudget(array $row)
    {
        $possibleKeys = [
            'budget', 'budget_amount', 'spent', 'spend', 'amount', 
            'cost', 'total_budget', 'total_spend', 'investment',
            'ad_spend', 'ad_spent', 'campaign_spend'
        ];
        
        $budget = $this->getValue($row, $possibleKeys, 0);
        return $this->normalizeBudget($budget);
    }

    private function extractPlatform(array $row)
    {
        $platform = $this->getValue($row, ['platform', 'platform_name', 'network', 'ad_platform', 'ad_network'], '');
        return $this->normalizePlatform($platform);
    }

    private function extractCountry(array $row)
    {
        $possibleKeys = [
            'country', 'country_name', 'location', 'market', 
            'geo', 'region', 'territory', 'geo_location'
        ];
        
        return $this->getValue($row, $possibleKeys, 'Unknown');
    }

    private function normalizeAgency($agency)
    {
        if (empty($agency) || $agency === 'Unknown Agency' || $agency === 'N/A' || $agency === 'null' || $agency === '(null)' || $agency === 'NULL') {
            return 'direct';
        }
        
        $agency = trim($agency);
        
        // Handle Chinese agency name "直投" which means "direct"
        if ($agency === '直投') {
            return 'direct';
        }
        
        $directVariations = ['direct', 'none', 'self', 'in-house', 'inhouse', 'internal', '-', ''];
        if (in_array(strtolower($agency), $directVariations)) {
            return 'direct';
        }
        
        return $agency;
    }

    private function getValue(array $row, array $possibleKeys, $default = null)
    {
        foreach ($possibleKeys as $key) {
            // Try exact match first
            if (array_key_exists($key, $row) && isset($row[$key]) && $row[$key] !== '' && $row[$key] !== 'NULL') {
                return $this->cleanValue($row[$key]);
            }
            
            // Try case-insensitive match
            foreach ($row as $rowKey => $value) {
                if ($value === '' || $value === 'NULL') continue;
                
                $normalizedRowKey = strtolower(str_replace([' ', '-', '_'], '', $rowKey));
                $normalizedPossibleKey = strtolower(str_replace([' ', '-', '_'], '', $key));
                
                if ($normalizedRowKey === $normalizedPossibleKey && isset($value)) {
                    return $this->cleanValue($value);
                }
            }
        }
        
        return $default;
    }

    private function cleanValue($value)
    {
        if (is_null($value) || $value === '' || $value === 'NULL') {
            return null;
        }

        // Handle numeric strings with commas
        if (is_numeric(str_replace([',', '$', ' ', '€', '£'], '', $value))) {
            return $value;
        }

        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    private function normalizeBudget($budget)
    {
        if (is_null($budget) || $budget === '' || $budget === 'NULL') {
            return 0;
        }

        if (is_string($budget)) {
            $budget = str_replace(['$', ',', '€', '£', ' '], '', $budget);
            
            // Handle negative numbers in parentheses
            if (strpos($budget, '(') !== false && strpos($budget, ')') !== false) {
                $budget = '-' . str_replace(['(', ')'], '', $budget);
            }
        }

        return floatval($budget);
        // Don't convert negative to 0 here - handle it in the model method
    }

    private function normalizePlatform($platform)
    {
        if (is_null($platform) || $platform === '' || $platform === 'NULL') {
            return '';
        }

        $platform = strtolower(trim($platform));
        
        $platformMap = [
            'google' => 'google',
            'google ads' => 'google',
            'googles' => 'google',
            'google adwords' => 'google',
            'snap' => 'snap', 
            'snapchat' => 'snap',
            'snaps' => 'snap',
            'tiktok' => 'tiktok',
            'tik tok' => 'tiktok',
            'tiktoks' => 'tiktok',
            'meta' => 'meta',
            'facebook' => 'meta',
            'instagram' => 'meta',
            'metas' => 'meta',
            'twitter' => 'twitter',
            'x' => 'twitter',
            'twitters' => 'twitter',
        ];

        return $platformMap[$platform] ?? $platform;
    }

    public function rules(): array
    {
        return [
            // Make all fields nullable and remove strict validation
            '*.client' => 'nullable|string|max:255',
            '*.agency' => 'nullable|string|max:255', 
            '*.budget' => 'nullable|numeric', // Removed min:0 to allow negative values temporarily
            '*.platform' => 'nullable|string|max:50',
            '*.country' => 'nullable|string|max:100',
            '*.date' => 'nullable', // Make date validation more flexible
        ];
    }

    public function prepareForValidation($data, $index)
    {
        // Log the data being validated for debugging
        \Log::info("Validating row {$index}:", $data);
        
        // Convert empty strings to null for nullable fields
        foreach ($data as $key => $value) {
            if ($value === '') {
                $data[$key] = null;
            }
        }
        
        return $data;
    }

    public function getRowCount(): int
    {
        return $this->successfulRows;
    }
}