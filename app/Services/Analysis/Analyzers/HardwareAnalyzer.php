<?php

namespace App\Services\Analysis\Analyzers;

use App\Services\Analysis\AnalysisContext;
use App\Services\Analysis\AnalyzerInterface;
use App\Services\Analysis\FindingData;

/**
 * HW-AGE: edad del hardware según el odómetro (días de servicio por slot).
 */
class HardwareAnalyzer implements AnalyzerInterface
{
    public function analyze(AnalysisContext $ctx): array
    {
        $rule = $ctx->rule('HW-AGE');
        if ($rule === null) {
            return [];
        }

        $findings = [];

        foreach ($ctx->parsed->odometerDays as $slot => $days) {
            $years = round($days / 365, 1);
            $level = $ctx->severityFor($rule, $years);

            if ($level === null) {
                continue;
            }

            $findings[] = new FindingData(
                ruleCode: 'HW-AGE',
                level: $level,
                area: 'hardware',
                entity: $slot,
                title: "Hardware de {$slot} con ~{$years} años de servicio",
                description: "El odómetro de {$slot} registra ".number_format($days).
                    " días de servicio (~{$years} años).",
                impact: 'Mayor probabilidad de falla de componentes (ventiladores, PSU, capacitores) y posible EOL.',
                recommendation: 'Verificar el estado de soporte del modelo (End of Sale/Support de Extreme) y '.
                    'considerar el reemplazo en el plan de ciclo de vida.',
                evidence: null,
                fileLocation: 'show odometers',
            );
        }

        return $findings;
    }
}
