<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Metric extends Model
{
    protected $fillable = [
        'capture_id',
        'category',
        'entity',
        'metric',
        'value',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'extra' => 'array',
        ];
    }

    public function capture(): BelongsTo
    {
        return $this->belongsTo(Capture::class);
    }
}
