<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FindingAttachment extends Model
{
    protected $fillable = [
        'finding_id',
        'type',
        'path',
        'original_filename',
        'caption',
    ];

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class);
    }
}
