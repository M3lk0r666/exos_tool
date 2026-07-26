<?php

namespace App\Services\Reporting;

use App\Enums\FindingSeverity;
use Illuminate\Support\Collection;

/**
 * Semáforo por área (sección 5.6): calcula el estado de cada área según
 * la peor severidad de sus hallazgos.
 */
class AreaStatusService
{
    /** @var array<string, string> clave interna => etiqueta en español */
    public const AREAS = [
        'stability' => 'Estabilidad',
        'ports' => 'Puertos',
        'firmware' => 'Firmware',
        'hardware' => 'Hardware',
        'environment' => 'Ambiente',
        'power' => 'Alimentación',
        'stacking' => 'Stacking',
        'cpu_memory' => 'CPU / Memoria',
        'management' => 'Gestión',
        'security' => 'Seguridad',
    ];

    public static function label(string $area): string
    {
        return self::AREAS[$area] ?? ucfirst($area);
    }

    /**
     * @param  Collection<int, \App\Models\Finding>  $findings
     * @return array<string, array{label: string, status: string, worst: ?FindingSeverity, count: int}>
     */
    public function compute(Collection $findings): array
    {
        $result = [];

        foreach (self::AREAS as $area => $label) {
            $areaFindings = $findings->where('area', $area)
                ->filter(fn ($f) => $f->status !== \App\Enums\FindingStatus::FalsePositive);

            $worst = $areaFindings
                ->map(fn ($f) => $f->level)
                ->sortByDesc(fn (FindingSeverity $l) => $l->weight())
                ->first();

            $result[$area] = [
                'label' => $label,
                'status' => $this->statusFor($worst),
                'worst' => $worst,
                'count' => $areaFindings->count(),
            ];
        }

        return $result;
    }

    /** verde | amarillo | naranja | rojo | gris (sin datos) */
    private function statusFor(?FindingSeverity $worst): string
    {
        return match ($worst) {
            null => 'ok',
            FindingSeverity::Informational, FindingSeverity::Low => 'ok',
            FindingSeverity::Medium => 'warning',
            FindingSeverity::High => 'severe',
            FindingSeverity::Critical => 'critical',
        };
    }

    /** Clases Tailwind para la tarjeta del semáforo. */
    public static function cardClasses(string $status): string
    {
        return match ($status) {
            'ok' => 'bg-green-50 border-green-300 text-green-800 dark:bg-green-900/30 dark:border-green-700 dark:text-green-300',
            'warning' => 'bg-yellow-50 border-yellow-300 text-yellow-800 dark:bg-yellow-900/30 dark:border-yellow-700 dark:text-yellow-300',
            'severe' => 'bg-red-50 border-red-400 text-red-700 dark:bg-red-900/30 dark:border-red-600 dark:text-red-300',
            'critical' => 'bg-red-100 border-red-500 text-red-800 dark:bg-red-900/50 dark:border-red-500 dark:text-red-200',
            default => 'bg-gray-50 border-gray-200 text-gray-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400',
        };
    }

    /** Color hex para el PDF. */
    public static function pdfColor(string $status): string
    {
        return match ($status) {
            'ok' => '#16a34a',
            'warning' => '#ca8a04',
            'severe' => '#e11d48',   // rojo (Alto): mayor atención, sin igualar al crítico
            'critical' => '#b91c1c', // rojo profundo (Crítico)
            default => '#6b7280',
        };
    }
}
