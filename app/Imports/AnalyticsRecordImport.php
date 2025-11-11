<?php

namespace App\Imports;

use App\Models\AnalyticsRecord;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class AnalyticsRecordImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    use SkipsErrors, SkipsFailures;

    private $sourceFile;
    private $successfulRows = 0;

    public function __construct($sourceFile)
    {
        $this->sourceFile = $sourceFile;
    }

public function model(array $row)
{
    \Log::info('Processing row:', $row);

    // Normalize and validate data
    $client = $this->getValue($row, ['client', 'client_name'], 'Unknown Client');
    $agency = $this->getValue($row, ['agency', 'agency_name'], 'direct'); // Default to 'direct'
    $budget = $this->normalizeBudget($this->getValue($row, ['budget', 'budget_amount'], 0));
    $platform = $this->normalizePlatform($this->getValue($row, ['platform', 'platform_name'], 'google'));
    $country = $this->getValue($row, ['country', 'country_name'], 'Unknown');

    // Normalize agency - if empty, null, or specific values, set to 'direct'
    $agency = $this->normalizeAgency($agency);

    \Log::info('Normalized data:', [
        'client' => $client,
        'agency' => $agency,
        'budget' => $budget,
        'platform' => $platform,
        'country' => $country,
        'source_file' => $this->sourceFile
    ]);

    // Skip if essential data is missing
    if (empty($client) || $client === 'Unknown Client') {
        \Log::warning('Skipping row: Missing client data');
        return null;
    }

    $this->successfulRows++;

    try {
        $record = new AnalyticsRecord([
            'client' => $client,
            'agency' => $agency,
            'budget' => $budget,
            'platform' => $platform,
            'country' => $country,
            'source_file' => $this->sourceFile,
        ]);

        \Log::info('Created record:', $record->toArray());
        return $record;

    } catch (\Exception $e) {
        \Log::error('Error creating record: ' . $e->getMessage());
        return null;
    }
}

private function normalizeAgency($agency)
{
    if (empty($agency) || $agency === 'Unknown Agency' || $agency === 'N/A' || $agency === 'null') {
        return 'direct';
    }
    
    $agency = trim($agency);
    
    // Common variations that should be considered as direct
    $directVariations = ['direct', 'none', 'self', 'in-house', 'inhouse', 'internal'];
    if (in_array(strtolower($agency), $directVariations)) {
        return 'direct';
    }
    
    return $agency;
}
public function rules(): array
{
    return [
        '*.client' => 'nullable|string',
        '*.agency' => 'nullable|string', 
        '*.budget' => 'nullable|numeric', // Remove min:0 to allow negative values
        '*.platform' => 'nullable|string',
        '*.country' => 'nullable|string',
    ];
}

    private function getValue(array $row, array $possibleKeys, $default = null)
    {
        foreach ($possibleKeys as $key) {
            $lowerKey = strtolower($key);
            foreach ($row as $rowKey => $value) {
                if (strtolower($rowKey) === $lowerKey && isset($value) && $value !== '') {
                    return $this->cleanValue($value);
                }
            }
            
            // Also check for keys with spaces replaced by underscores (common in Excel)
            $underscoreKey = str_replace(' ', '_', $lowerKey);
            foreach ($row as $rowKey => $value) {
                if (strtolower($rowKey) === $underscoreKey && isset($value) && $value !== '') {
                    return $this->cleanValue($value);
                }
            }
        }
        
        return $default;
    }

    private function cleanValue($value)
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

private function normalizeBudget($budget)
{
    if (is_null($budget) || $budget === '') {
        return 0;
    }

    if (is_string($budget)) {
        $budget = str_replace(['$', ',', '€', '£', ' '], '', $budget);
        
        // Handle negative numbers in strings
        if (strpos($budget, '(') !== false && strpos($budget, ')') !== false) {
            $budget = '-' . str_replace(['(', ')'], '', $budget);
        }
    }

    $budget = floatval($budget);
    
    // If budget is negative, set to 0 and log a warning
    if ($budget < 0) {
        \Log::warning("Negative budget found: {$budget}. Setting to 0.");
        return 0;
    }
    
    return $budget;
}
    private function normalizePlatform($platform)
    {
        if (is_null($platform) || $platform === '') {
            return 'google';
        }

        $platform = strtolower(trim($platform));
        
        $platformMap = [
            'google' => 'google',
            'google ads' => 'google',
            'googles' => 'google',
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

        return $platformMap[$platform] ?? 'google';
    }

    public function getRowCount(): int
    {
        return $this->successfulRows;
    }
}