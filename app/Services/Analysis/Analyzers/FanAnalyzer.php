<?php

namespace App\Services\Analysis\Analyzers;

use App\Enums\FindingSeverity;
use App\Services\Analysis\AnalysisContext;
use App\Services\Analysis\AnalyzerInterface;
use App\Services\Analysis\FindingData;

/**
 * ENV-FAN: ventiladores o fan trays en falla. Los trays "Empty" NO son
 * hallazgo en chasis que los permiten (Anexo A).
 */
class FanAnalyzer implements AnalyzerInterface
{
    public function analyze(AnalysisContext $ctx): array
    {
        $rule = $ctx->rule('ENV-FAN');
        if ($rule === null) {
            return [];
        }

        $p = $ctx->parsed;
        $findings = [];
        $level = $rule->level_critical ? FindingSeverity::from($rule->level_critical) : FindingSeverity::Critical;

        foreach ($p->fansFailed as $fan) {
            $findings[] = new FindingData(
                ruleCode: 'ENV-FAN',
                level: $level,
                area: 'environment',
                entity: $fan,
                title: "Ventilador en falla: {$fan}",
                description: "El equipo reporta el ventilador {$fan} fuera de estado Operational. ".
                    'Este estado lo declara el propio switch.',
                impact: 'Reducción de la capacidad de enfriamiento; riesgo térmico si fallan más ventiladores.',
                recommendation: 'Programar reemplazo del fan tray. Verificar temperaturas del equipo mientras tanto.',
                evidence: $ctx->findEvidence($fan)['text'] ?? null,
                fileLocation: 'show fans',
            );
        }

        foreach ($p->fanTrays as $tray => $state) {
            if (! in_array($state, ['Operational', 'Empty'], true)) {
                $findings[] = new FindingData(
                    ruleCode: 'ENV-FAN',
                    level: $level,
                    area: 'environment',
                    entity: $tray,
                    title: "Fan tray en estado {$state}: {$tray}",
                    description: "El fan tray {$tray} reporta estado \"{$state}\".",
                    impact: 'Pérdida de capacidad de enfriamiento en el slot afectado.',
                    recommendation: 'Programar reemplazo del fan tray en ventana de mantenimiento.',
                    evidence: null,
                    fileLocation: 'show fans',
                );
            }
        }

        return $findings;
    }
}
