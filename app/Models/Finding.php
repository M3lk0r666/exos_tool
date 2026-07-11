<?php

namespace App\Models;

use App\Enums\FindingSeverity;
use App\Enums\FindingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Finding extends Model
{
    /** @use HasFactory<\Database\Factories\FindingFactory> */
    use HasFactory;

    protected $fillable = [
        'capture_id',
        'device_id',
        'rule_code',
        'level',
        'area',
        'entity',
        'title',
        'description',
        'impact',
        'recommendation',
        'evidence',
        'file_location',
        'status',
        'status_notes',
        'is_manual',
        'first_seen_capture_id',
        'edited_by',
    ];

    protected function casts(): array
    {
        return [
            'level' => FindingSeverity::class,
            'status' => FindingStatus::class,
            'is_manual' => 'boolean',
        ];
    }

    public function capture(): BelongsTo
    {
        return $this->belongsTo(Capture::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function firstSeenCapture(): BelongsTo
    {
        return $this->belongsTo(Capture::class, 'first_seen_capture_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(FindingAttachment::class);
    }
}
