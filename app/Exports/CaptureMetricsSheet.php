<?php

namespace App\Exports;

use App\Models\Capture;
use App\Models\Metric;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class CaptureMetricsSheet implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private readonly Capture $capture) {}

    public function collection()
    {
        return $this->capture->metrics()
            ->orderBy('category')->orderBy('entity')->orderBy('metric')
            ->get();
    }

    public function headings(): array
    {
        return ['Categoría', 'Entidad', 'Métrica', 'Valor', 'Extra'];
    }

    /** @param Metric $metric */
    public function map($metric): array
    {
        return [
            $metric->category,
            $metric->entity,
            $metric->metric,
            (float) $metric->value,
            $metric->extra ? json_encode($metric->extra, JSON_UNESCAPED_UNICODE) : '',
        ];
    }

    public function title(): string
    {
        return 'Métricas';
    }
}
