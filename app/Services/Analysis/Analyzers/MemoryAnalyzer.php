<?php

namespace App\Services\Analysis\Analyzers;

use App\Services\Analysis\AnalysisContext;
use App\Services\Analysis\AnalyzerInterface;
use App\Services\Analysis\FindingData;

/**
 * MEM-FREE: porcentaje de memoria libre por slot (umbral hacia abajo).
 */
class MemoryAnalyzer implements AnalyzerInterface
{
    public function analyze(AnalysisContext $ctx): array
    {
        $rule = $ctx->rule('MEM-FREE');
        if ($rule === null) {
            return [];
        }

        $findings = [];

        foreach ($ctx->parsed->memoryBySlot as $slot => $mem) {
            if ($mem['total_kb'] <= 0) {
                continue;
            }

            $freePct = round($mem['free_kb'] / $mem['total_kb'] * 100, 1);
            $level = $ctx->severityFor($rule, $freePct);

            if ($level === null) {
                continue;
            }

            $findings[] = new FindingData(
                ruleCode: 'MEM-FREE',
                level: $level,
                area: 'cpu_memory',
                entity: $slot,
                title: "Memoria libre baja en {$slot}: {$freePct}%",
                description: "{$slot} reporta ".number_format($mem['free_kb'])." KB libres de ".
                    number_format($mem['total_kb'])." KB ({$freePct}% libre).",
                impact: 'Memoria insuficiente puede degradar procesos del sistema o impedir cambios de configuración.',
                recommendation: 'Comparar contra capturas anteriores: una disminución sostenida sugiere fuga de memoria '.
                    '(conocida en algunas versiones EXOS; revisar release notes o consultar GTAC).',
                evidence: null,
                fileLocation: 'show memory',
            );
        }

        return $findings;
    }
}
