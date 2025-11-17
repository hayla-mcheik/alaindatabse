<?php

namespace App\Imports;

use App\Models\AnalyticsRecord;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Validators\Failure;
use Carbon\Carbon;

class AnalyticsRecordImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnFailure
{
    use SkipsFailures;

    private $sourceFile;
    private $successfulRows = 0;
    private $isExportFile = false;
    private $existingRecords = [];
    private $skippedRows = []; // Track skipped rows with reasons
    private $currentRowIndex = 0; // Track current row index for better error reporting

    public function __construct($sourceFile)
    {
        $this->sourceFile = $sourceFile;
        $this->isExportFile = $this->isExportFileFormat($sourceFile);
        
        // Pre-load existing records for this source file to check for updates
        $this->loadExistingRecords();
    }

    private function loadExistingRecords()
    {
        // Load existing records for this source file to check for updates
        $this->existingRecords = AnalyticsRecord::where('source_file', $this->sourceFile)
            ->get()
            ->keyBy(function ($record) {
                return $this->generateRecordKey($record);
            });
    }

    private function generateRecordKey($data)
    {
        // Create a unique key based on client, platform, country, and year
        $client = $data['client'] ?? '';
        $platform = $data['platform'] ?? '';
        $country = $data['country'] ?? '';
        $year = isset($data['date']) ? Carbon::parse($data['date'])->format('Y') : '';
        
        return md5("{$client}_{$platform}_{$country}_{$year}");
    }

    public function model(array $row)
    {
        $this->currentRowIndex++;
        \Log::info("Processing row {$this->currentRowIndex}:", $row);

        // Skip empty rows
        if ($this->isEmptyRow($row)) {
            \Log::info('Skipping empty row');
            $this->trackSkippedRow($row, 'Empty row');
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
                $this->trackSkippedRow($row, 'Missing or invalid client data');
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
                $this->trackSkippedRow($row, 'Invalid budget (zero or negative)');
                return null;
            }

            // Check if date is valid
            if (empty($data['date'])) {
                \Log::warning('Skipping row: Invalid or missing date');
                $this->trackSkippedRow($row, 'Invalid or missing date');
                return null;
            }

            // Check if record already exists for this source file
            $recordKey = $this->generateRecordKey($data);
            
            if (isset($this->existingRecords[$recordKey])) {
                // Update existing record
                $existingRecord = $this->existingRecords[$recordKey];
                \Log::info('Updating existing record', [
                    'existing_id' => $existingRecord->id,
                    'old_budget' => $existingRecord->budget,
                    'new_budget' => $data['budget'],
                    'client' => $data['client']
                ]);

                // Update the record
                $existingRecord->update([
                    'agency' => $data['agency'],
                    'budget' => $data['budget'],
                    'source_file' => $this->sourceFile,
                    'updated_at' => now(),
                ]);

                $this->successfulRows++;
                return null; // Return null since we're updating, not creating
            } else {
                // Create new record
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

                \Log::info('Creating new record:', $record->toArray());
                return $record;
            }

        } catch (\Exception $e) {
            \Log::error('Error processing record: ' . $e->getMessage());
            \Log::error('Row data that caused error:', $row);
            $this->trackSkippedRow($row, 'Processing error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Track skipped rows with reasons
     */
    private function trackSkippedRow(array $row, string $reason)
    {
        $this->skippedRows[] = [
            'row_data' => $row,
            'reason' => $reason,
            'file' => $this->sourceFile,
            'row_index' => $this->currentRowIndex + 1 // +1 because heading row is row 1
        ];
    }

    /**
     * Get all skipped rows with reasons
     */
    public function getSkippedRows(): array
    {
        return $this->skippedRows;
    }

    /**
     * Get count of skipped rows
     */
    public function getSkippedRowsCount(): int
    {
        return count($this->skippedRows);
    }

    /**
     * Get comprehensive import statistics
     */
    public function getImportStats(): array
    {
        return [
            'successful_rows' => $this->successfulRows,
            'skipped_rows' => $this->skippedRows,
            'skipped_count' => $this->getSkippedRowsCount(),
            'failures' => $this->failures(),
            'failure_count' => count($this->failures()),
        ];
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
        // For export files (your system's export structure)
        return [
            'client' => $this->getValue($row, ['client', 'client_name'], 'Unknown Client'),
            'agency' => $this->normalizeAgency($this->getValue($row, ['agency', 'agency_name'], 'direct')),
            'budget' => $this->normalizeBudget($this->getValue($row, ['budget', 'budget_amount'], 0)),
            'platform' => $this->normalizePlatform($this->getValue($row, ['platform', 'platform_name'], $this->determinePlatformFromFilename($this->sourceFile))),
            'country' => $this->getValue($row, ['country', 'country_name'], 'Unknown'),
            'date' => $this->extractDate($row),
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
            'date' => $this->extractDate($row),
        ];
    }

    private function extractDate(array $row)
    {
        $possibleKeys = [
            'date', 'transaction_date', 'report_date', 'period', 
            'month', 'year', 'time_period', 'date_range'
        ];
        
        $dateValue = $this->getValue($row, $possibleKeys, null);
        return $this->normalizeDate($dateValue);
    }

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
            '*.budget' => 'nullable|numeric',
            '*.platform' => 'nullable|string|max:50',
            '*.country' => 'nullable|string|max:100',
            '*.date' => 'nullable',
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

    /**
     * Get total processed rows (successful + skipped)
     */
    public function getTotalProcessedRows(): int
    {
        return $this->successfulRows + $this->getSkippedRowsCount();
    }
}