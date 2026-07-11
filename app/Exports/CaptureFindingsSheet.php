<?php

namespace App\Exports;

use App\Models\Capture;
use App\Models\Finding;
use App\Services\Reporting\AreaStatusService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class CaptureFindingsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly Capture $capture) {}

    public function collection()
    {
        return $this->capture->findings()->orderByRaw(
            "CASE level WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ".
            "WHEN 'low' THEN 4 ELSE 5 END"
        )->get();
    }

    public function headings(): array
    {
        return ['Regla', 'Severidad', 'Área', 'Entidad', 'Título', 'Descripción',
            'Impacto', 'Recomendación', 'Estado', 'Ubicación', 'Manual'];
    }

    /** @param Finding $finding */
    public function map($finding): array
    {
        return [
            $finding->rule_code,
            $finding->level->label(),
            AreaStatusService::label($finding->area),
            $finding->entity,
            $finding->title,
            $finding->description,
            $finding->impact,
            $finding->recommendation,
            $finding->status->label(),
            $finding->file_location,
            $finding->is_manual ? 'Sí' : 'No',
        ];
    }

    public function title(): string
    {
        return 'Hallazgos';
    }
}
