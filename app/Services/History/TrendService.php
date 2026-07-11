<?php

namespace App\Services\History;

use App\Models\Device;
use App\Models\Finding;
use App\Models\Metric;

/**
 * Series de tendencia por equipo para ApexCharts (sección 5.8).
 * Eje X = fecha de captura (del propio archivo).
 */
class TrendService
{
    public function forDevice(Device $device): array
    {
        $captures = $device->captures()
            ->where('status', 'completed')
            ->whereNotNull('captured_at')
            ->orderBy('captured_at')
            ->get(['id', 'captured_at']);

        if ($captures->isEmpty()) {
            return ['labels' => [], 'temperature' => [], 'memory' => [], 'cpu' => [], 'crc' => [], 'severity' => []];
        }

        $captureIds = $captures->pluck('id');
        $labels = $captures->mapWithKeys(
            fn ($c) => [$c->id => $c->captured_at->format('d/m/Y H:i')]
        );

        $metrics = Metric::whereIn('capture_id', $captureIds)
            ->whereIn('metric', ['temperature', 'free_pct', 'util_1h_pct', 'crc_errors'])
            ->get(['capture_id', 'category', 'entity', 'metric', 'value']);

        // Temperatura por unidad (solo categoría env)
        $temperature = $metrics->where('metric', 'temperature')->where('category', 'env')
            ->groupBy('entity')
            ->map(fn ($group, $unit) => [
                'name' => $unit,
                'data' => $captureIds->map(fn ($id) => [
                    'x' => $labels[$id],
                    'y' => ($v = $group->firstWhere('capture_id', $id)?->value) !== null ? (float) $v : null,
                ])->all(),
            ])->values()->all();

        // % memoria libre por slot
        $memory = $metrics->where('metric', 'free_pct')
            ->groupBy('entity')
            ->map(fn ($group, $slot) => [
                'name' => $slot,
                'data' => $captureIds->map(fn ($id) => [
                    'x' => $labels[$id],
                    'y' => ($v = $group->firstWhere('capture_id', $id)?->value) !== null ? (float) $v : null,
                ])->all(),
            ])->values()->all();

        // CPU 1h (una serie)
        $cpuMetrics = $metrics->where('metric', 'util_1h_pct');
        $cpu = $cpuMetrics->isEmpty() ? [] : [[
            'name' => 'CPU 1h (%)',
            'data' => $captureIds->map(fn ($id) => [
                'x' => $labels[$id],
                'y' => ($v = $cpuMetrics->firstWhere('capture_id', $id)?->value) !== null ? (float) $v : null,
            ])->all(),
        ]];

        // CRC total del equipo por captura
        $crcMetrics = $metrics->where('metric', 'crc_errors');
        $crc = [[
            'name' => 'Errores CRC totales',
            'data' => $captureIds->map(fn ($id) => [
                'x' => $labels[$id],
                'y' => (float) $crcMetrics->where('capture_id', $id)->sum('value'),
            ])->all(),
        ]];

        // Hallazgos por severidad por captura (barras apiladas)
        $findingCounts = Finding::whereIn('capture_id', $captureIds)
            ->selectRaw('capture_id, level, count(*) as total')
            ->groupBy('capture_id', 'level')
            ->get();

        $severity = collect(['critical', 'high', 'medium', 'low', 'informational'])
            ->map(fn ($level) => [
                'name' => \App\Enums\FindingSeverity::from($level)->label(),
                'data' => $captureIds->map(
                    fn ($id) => (int) ($findingCounts->first(
                        fn ($r) => $r->capture_id === $id && $r->level->value === $level
                    )?->total ?? 0)
                )->all(),
            ])->all();

        return [
            'labels' => $labels->values()->all(),
            'temperature' => $temperature,
            'memory' => $memory,
            'cpu' => $cpu,
            'crc' => $crc,
            'severity' => $severity,
        ];
    }
}
