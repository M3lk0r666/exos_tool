<?php

namespace App\Models;

use App\Enums\CaptureStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Capture extends Model
{
    /** @use HasFactory<\Database\Factories\CaptureFactory> */
    use HasFactory;

    protected $fillable = [
        'device_id',
        'client_id',
        'analysis_type',
        'uploaded_by',
        'captured_at',
        'uploaded_at',
        'original_filename',
        'file_path',
        'file_hash',
        'file_size',
        'exos_version',
        'uptime_seconds',
        'boot_count',
        'status',
        'error_message',
        'parser_warnings',
        'raw_summary',
    ];

    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'uploaded_at' => 'datetime',
            'status' => CaptureStatus::class,
            'parser_warnings' => 'array',
            'raw_summary' => 'array',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(Metric::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function isLogAnalysis(): bool
    {
        return $this->analysis_type === 'log';
    }

    public function analysisTypeLabel(): string
    {
        return $this->isLogAnalysis() ? 'Log' : 'Tech-support';
    }
}
