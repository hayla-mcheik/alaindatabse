<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsRecord extends Model
{
    protected $table = 'analytics_records';
    
    protected $fillable = [
        'client',
        'agency', 
        'budget',
        'platform',
        'country',
        'source_file' // Add this
    ];

    protected $casts = [
        'budget' => 'decimal:2'
    ];
}