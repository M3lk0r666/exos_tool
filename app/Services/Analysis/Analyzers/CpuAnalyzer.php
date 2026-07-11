<?php

namespace App\Services\Analysis\Analyzers;

use App\Services\Analysis\AnalysisContext;
use App\Services\Analysis\AnalyzerInterface;
use App\Services\Analysis\FindingData;

/**
 * CPU-1H: utilización de CPU del sistema promedio a 1 hora.
 * Nota de dominio: NUNCA usar load average (un valor ~7 es normal en EXOS
 * por los hilos de kernel); solo el % de utilización.
 */
class CpuAnalyzer implements AnalyzerInterface
{
    public function analyze(AnalysisContext $ctx): array
    {
        $p = $ctx->parsed;
        $rule = $ctx->rule('CPU-1H');

        if ($rule === null || $p->cpuSystem1h === null) {
            return [];
        }

        $level = $ctx->severityFor($rule, $p->cpuSystem1h);
        if ($level === null) {
            return [];
        }

        $procs = $p->cpuHighProcesses !== []
            ? ' Procesos con mayor consumo (1h): '.implode(', ', array_map(
                fn ($proc) => "{$proc['process']} ({$proc['util_1h']}%)",
                $p->cpuHighProcesses
            )).'.'
            : '';

        $evidence = $ctx->findEvidenceRegex('/^System\s+[\d.]+\s/');

        return [new FindingData(
            ruleCode: 'CPU-1H',
            level: $level,
            area: 'cpu_memory',
            entity: 'System',
            title: "CPU del sistema al {$p->cpuSystem1h}% (promedio 1 hora)",
            description: 'La utilización de CPU del sistema promedió '.$p->cpuSystem1h.'% durante la última hora.'.$procs,
            impact: 'CPU alta sostenida puede retrasar protocolos de control (STP, routing, LACP) y la gestión del equipo.',
            recommendation: 'Identificar el proceso responsable y su causa (tormentas de broadcast, SNMP polling '.
                'excesivo, telemetría). Comparar contra capturas anteriores para ver tendencia.',
            evidence: $evidence['text'] ?? null,
            fileLocation: $evidence ? 'línea '.$evidence['line'].' (show cpu-monitoring)' : 'show cpu-monitoring',
        )];
    }
}
