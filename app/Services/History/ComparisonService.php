<?php

namespace App\Services\History;

use App\Models\Capture;
use App\Models\Finding;
use Illuminate\Support\Collection;

/**
 * Comparativo entre dos capturas del mismo equipo (sección 5.8).
 *
 * Manejo de reinicio de contadores: los contadores de puertos son acumulados
 * desde el último boot; si la captura nueva tiene uptime menor que la anterior,
 * hubo reinicio y NO se calculan deltas de contadores.
 */
class ComparisonService
{
    public function compare(Capture $old, Capture $new): array
    {
        // Orden cronológico garantizado
        if ($old->captured_at !== null && $new->captured_at !== null
            && $old->captured_at->gt($new->captured_at)) {
            [$old, $new] = [$new, $old];
        }

        $rebootDetected = $old->uptime_seconds !== null
            && $new->uptime_seconds !== null
            && $new->uptime_seconds < $old->uptime_seconds;

        return [
            'old' => $old,
            'new' => $new,
            'reboot_detected' => $rebootDetected,
            'general' => $this->generalRows($old, $new),
            'environment' => $this->environmentRows($old, $new),
            'ports' => $this->portRows($old, $new, $rebootDetected),
            'findings' => $this->findingsDiff($old, $new),
        ];
    }

    /** @return array<string, array{old: mixed, new: mixed, change: string}> */
    private function generalRows(Capture $old, Capture $new): array
    {
        $so = $old->raw_summary ?? [];
        $sn = $new->raw_summary ?? [];

        $rows = [];

        $rows['Versión EXOS'] = $this->row($old->exos_version, $new->exos_version,
            $old->exos_version === $new->exos_version ? 'same' : 'info');

        $rows['Uptime'] = $this->row(
            $so['uptime_text'] ?? null,
            $sn['uptime_text'] ?? null,
            ($new->uptime_seconds ?? 0) < ($old->uptime_seconds ?? 0) ? 'worse' : 'same'
        );

        $rows['Boot count'] = $this->row($old->boot_count, $new->boot_count,
            ($new->boot_count ?? 0) > ($old->boot_count ?? 0) ? 'worse' : 'same');

        $rows['Ventiladores OK'] = $this->deltaRow($so['fans']['ok'] ?? null, $sn['fans']['ok'] ?? null, higherIsBetter: true);
        $rows['PSU en falla'] = $this->deltaRow($so['power']['failed'] ?? null, $sn['power']['failed'] ?? null, higherIsBetter: false);
        $rows['CPU 1h (%)'] = $this->deltaRow($so['cpu_1h'] ?? null, $sn['cpu_1h'] ?? null, higherIsBetter: false);
        $rows['Eventos de error en log'] = $this->deltaRow($so['logs']['errors'] ?? null, $sn['logs']['errors'] ?? null, higherIsBetter: false);

        return $rows;
    }

    /** @return array<string, array{old: mixed, new: mixed, change: string}> */
    private function environmentRows(Capture $old, Capture $new): array
    {
        $rows = [];

        // Temperatura por unidad
        $oldTemps = collect(($old->raw_summary ?? [])['temperatures'] ?? [])->keyBy('unit');
        $newTemps = collect(($new->raw_summary ?? [])['temperatures'] ?? [])->keyBy('unit');

        foreach ($newTemps as $unit => $t) {
            $prev = $oldTemps->get($unit);
            $rows["Temperatura {$unit} (°C)"] = $this->deltaRow(
                $prev['temp'] ?? null,
                $t['temp'],
                higherIsBetter: false,
                tolerance: 2.0
            );
        }

        // Memoria libre (%) por slot
        $oldMem = ($old->raw_summary ?? [])['memory'] ?? [];
        $newMem = ($new->raw_summary ?? [])['memory'] ?? [];

        foreach ($newMem as $slot => $mem) {
            $newPct = $mem['total_kb'] > 0 ? round($mem['free_kb'] / $mem['total_kb'] * 100, 1) : null;
            $oldPct = isset($oldMem[$slot]) && $oldMem[$slot]['total_kb'] > 0
                ? round($oldMem[$slot]['free_kb'] / $oldMem[$slot]['total_kb'] * 100, 1)
                : null;

            $rows["Memoria libre {$slot} (%)"] = $this->deltaRow($oldPct, $newPct, higherIsBetter: true, tolerance: 1.0);
        }

        return $rows;
    }

    /**
     * Deltas por puerto de contadores acumulados (CRC, fragmentos, flapping).
     *
     * @return array{reset: bool, rows: array<int, array{port: string, metric: string, old: ?float, new: ?float, delta: ?float}>}
     */
    private function portRows(Capture $old, Capture $new, bool $rebootDetected): array
    {
        $metrics = ['crc_errors' => 'CRC', 'fragments' => 'Fragmentos', 'link_transitions' => 'Transiciones de link'];

        $oldValues = $this->portMetrics($old, array_keys($metrics));
        $newValues = $this->portMetrics($new, array_keys($metrics));

        $rows = [];

        foreach ($metrics as $metric => $label) {
            $ports = collect(array_keys(($newValues[$metric] ?? []) + ($oldValues[$metric] ?? [])))
                ->sort(SORT_NATURAL);

            foreach ($ports as $port) {
                $o = $oldValues[$metric][$port] ?? null;
                $n = $newValues[$metric][$port] ?? null;

                // Con filtros "| exclude", puerto ausente = 0 (Anexo A)
                $oComparable = $o ?? 0.0;
                $nComparable = $n ?? 0.0;

                $delta = $rebootDetected ? null : $nComparable - $oComparable;

                // Solo mostrar filas relevantes: valor actual > 0 o delta ≠ 0
                if ($nComparable == 0 && ($delta === null || $delta == 0)) {
                    continue;
                }

                $rows[] = [
                    'port' => (string) $port,
                    'metric' => $label,
                    'old' => $o,
                    'new' => $n,
                    'delta' => $delta,
                ];
            }
        }

        return ['reset' => $rebootDetected, 'rows' => $rows];
    }

    /** @return array<string, array<string, float>> metric => [port => value] */
    private function portMetrics(Capture $capture, array $metrics): array
    {
        $result = [];

        $capture->metrics()
            ->where('category', 'ports')
            ->whereIn('metric', $metrics)
            ->get(['entity', 'metric', 'value'])
            ->each(function ($m) use (&$result) {
                $result[$m->metric][$m->entity] = (float) $m->value;
            });

        return $result;
    }

    /**
     * @return array{new: Collection, resolved: Collection, persisting: Collection}
     */
    private function findingsDiff(Capture $old, Capture $new): array
    {
        $key = fn (Finding $f) => $f->rule_code.'|'.$f->entity;

        $oldFindings = $old->findings()->get()->keyBy($key);
        $newFindings = $new->findings()->get()->keyBy($key);

        return [
            'new' => $newFindings->diffKeys($oldFindings)->values(),
            'resolved' => $oldFindings->diffKeys($newFindings)->values(),
            'persisting' => $newFindings->intersectByKeys($oldFindings)->values(),
        ];
    }

    private function row(mixed $old, mixed $new, string $change): array
    {
        return ['old' => $old, 'new' => $new, 'change' => $change];
    }

    /** Cambio con dirección: better | worse | same | info. */
    private function deltaRow(mixed $old, mixed $new, bool $higherIsBetter, float $tolerance = 0.0): array
    {
        if ($old === null || $new === null) {
            return $this->row($old, $new, 'info');
        }

        $delta = (float) $new - (float) $old;

        if (abs($delta) <= $tolerance) {
            return $this->row($old, $new, 'same');
        }

        $improved = $higherIsBetter ? $delta > 0 : $delta < 0;

        return $this->row($old, $new, $improved ? 'better' : 'worse');
    }
}
