<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyzerRule extends Model
{
    protected $fillable = [
        'code',
        'analyzer',
        'description',
        'threshold_warning',
        'threshold_critical',
        'level_warning',
        'level_critical',
        'enabled',
        'params',
    ];

    protected function casts(): array
    {
        return [
            'threshold_warning' => 'decimal:4',
            'threshold_critical' => 'decimal:4',
            'enabled' => 'boolean',
            'params' => 'array',
        ];
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }
}
