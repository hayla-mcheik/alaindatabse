<?php

namespace App\Imports;

use App\Models\AnalyticsRecord;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class AnalyticsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Each $row corresponds to one Excel row (by column header)
        return new AnalyticsRecord([
            'client'   => $row['client']   ?? null,
            'agency'   => $row['agency']   ?? null,
            'budget'   => $row['budget']   ?? null,
            'platform' => $row['platform'] ?? null,
            'country'  => $row['country']  ?? null,
        ]);
    }
}
