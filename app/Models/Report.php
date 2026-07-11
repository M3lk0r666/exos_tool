<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = [
        'capture_id',
        'version',
        'executive_summary',
        'conclusions',
        'recommendations',
        'status',
        'issued_by',
        'issued_at',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'issued_at' => 'datetime',
        ];
    }

    public function capture(): BelongsTo
    {
        return $this->belongsTo(Capture::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
