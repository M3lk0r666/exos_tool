<?php

namespace App\Services\Analysis;

use App\Enums\FindingStatus;
use App\Models\Capture;
use App\Models\Finding;
use Illuminate\Support\Collection;

/**
 * Ciclo de vida de hallazgos (sección 5.8): si un hallazgo equivalente
 * (mismo equipo + código de regla + entidad) existía en una captura anterior,
 * el nuevo se vincula al histórico en lugar de tratarse como nuevo:
 *  - hereda first_seen_capture_id (primera aparición real),
 *  - hereda estado y notas si estaba reconocido / en atención / falso positivo,
 *  - si estaba resuelto y reaparece, se reabre (open) con nota automática.
 */
class FindingLifecycleService
{
    /**
     * @return array{first_seen_capture_id: int, status: string, status_notes: ?string}
     */
    public function resolve(Capture $capture, FindingData $finding, Collection $previousFindings): array
    {
        $previous = $previousFindings
            ->first(fn (Finding $f) => $f->rule_code === $finding->ruleCode
                && (string) $f->entity === (string) $finding->entity);

        if ($previous === null) {
            return [
                'first_seen_capture_id' => $capture->id,
                'status' => FindingStatus::Open->value,
                'status_notes' => null,
            ];
        }

        $firstSeen = $previous->first_seen_capture_id ?? $previous->capture_id;

        if ($previous->status === FindingStatus::Resolved) {
            return [
                'first_seen_capture_id' => $firstSeen,
                'status' => FindingStatus::Open->value,
                'status_notes' => 'Reabierto automáticamente: el hallazgo marcado como resuelto '.
                    'reapareció en la captura #'.$capture->id.'.',
            ];
        }

        return [
            'first_seen_capture_id' => $firstSeen,
            'status' => $previous->status->value,
            'status_notes' => $previous->status_notes,
        ];
    }

    /**
     * Hallazgos más recientes por regla+entidad de capturas anteriores del equipo.
     *
     * @return Collection<int, Finding>
     */
    public function previousFindingsFor(Capture $capture): Collection
    {
        if ($capture->device_id === null) {
            return collect();
        }

        return Finding::query()
            ->where('device_id', $capture->device_id)
            ->where('capture_id', '!=', $capture->id)
            ->whereHas('capture', function ($q) use ($capture) {
                $q->where('captured_at', '<', $capture->captured_at ?? now());
            })
            ->orderByDesc('capture_id')
            ->get()
            // nos quedamos con la aparición más reciente de cada regla+entidad
            ->unique(fn (Finding $f) => $f->rule_code.'|'.$f->entity);
    }
}
