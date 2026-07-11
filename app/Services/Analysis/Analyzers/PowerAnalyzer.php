<?php

namespace App\Services\Analysis\Analyzers;

use App\Enums\FindingSeverity;
use App\Services\Analysis\AnalysisContext;
use App\Services\Analysis\AnalyzerInterface;
use App\Services\Analysis\FindingData;

/**
 * PWR-PSU: fuentes de poder en falla (estado declarado por el equipo).
 * Las bahías "Empty" no son hallazgo.
 */
class PowerAnalyzer implements AnalyzerInterface
{
    public function analyze(AnalysisContext $ctx): array
    {
        $rule = $ctx->rule('PWR-PSU');
        $p = $ctx->parsed;

        if ($rule === null || $p->psuFailed === 0) {
            return [];
        }

        $evidence = $ctx->findEvidenceRegex('/State\s*:\s*(Failed|Powered Off)/');

        return [new FindingData(
            ruleCode: 'PWR-PSU',
            level: $rule->level_critical ? FindingSeverity::from($rule->level_critical) : FindingSeverity::Critical,
            area: 'power',
            entity: 'System',
            title: $p->psuFailed === 1 ? 'Fuente de poder en falla' : "{$p->psuFailed} fuentes de poder en falla",
            description: "El equipo reporta {$p->psuFailed} fuente(s) de poder en estado Failed/Powered Off ".
                "({$p->psuOn} operativa(s)).",
            impact: $p->psuOn > 0
                ? 'Pérdida de redundancia eléctrica: una falla adicional apagaría el equipo.'
                : 'Riesgo inminente de apagado del equipo.',
            recommendation: 'Verificar alimentación de entrada (acometida/UPS/PDU) y reemplazar la PSU si la '.
                'alimentación es correcta.',
            evidence: $evidence['text'] ?? null,
            fileLocation: $evidence ? 'línea '.$evidence['line'].' (show power)' : 'show power',
        )];
    }
}
