<?php

namespace App\Exports;

use App\Models\Capture;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CaptureExport implements WithMultipleSheets
{
    public function __construct(private readonly Capture $capture) {}

    public function sheets(): array
    {
        return [
            new CaptureFindingsSheet($this->capture),
            new CaptureMetricsSheet($this->capture),
        ];
    }
}
