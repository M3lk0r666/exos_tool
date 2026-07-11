<?php

namespace App\Models;

use App\Enums\DeviceCriticality;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    /** @use HasFactory<\Database\Factories\DeviceFactory> */
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'system_mac',
        'serial_number',
        'sysname',
        'alias',
        'model',
        'is_stack',
        'site',
        'criticality',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_stack' => 'boolean',
            'criticality' => DeviceCriticality::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function captures(): HasMany
    {
        return $this->hasMany(Capture::class);
    }

    /** Última captura completada (para el semáforo del equipo). */
    public function latestCapture(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Capture::class)
            ->where('status', 'completed')
            ->latestOfMany('captured_at');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class);
    }

    /** Peor severidad de la última captura (null = sin hallazgos). */
    public function worstSeverity(): ?\App\Enums\FindingSeverity
    {
        $findings = $this->latestCapture?->findings;

        if ($findings === null || $findings->isEmpty()) {
            return null;
        }

        return $findings
            ->filter(fn ($f) => $f->status !== \App\Enums\FindingStatus::FalsePositive)
            ->map(fn ($f) => $f->level)
            ->sortByDesc(fn ($l) => $l->weight())
            ->first();
    }

    /** Nombre a mostrar: alias si existe, si no el sysname. */
    public function displayName(): string
    {
        return $this->alias ?: $this->sysname;
    }
}
