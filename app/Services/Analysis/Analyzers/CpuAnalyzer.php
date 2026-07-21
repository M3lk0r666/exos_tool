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
        $findings = $this->logCpuFindings($ctx);

        $rule = $ctx->rule('CPU-1H');

        if ($rule === null || $p->cpuSystem1h === null) {
            return $findings;
        }

        $level = $ctx->severityFor($rule, $p->cpuSystem1h);
        if ($level === null) {
            return $findings;
        }

        $procs = $p->cpuHighProcesses !== []
            ? ' Procesos con mayor consumo (1h): '.implode(', ', array_map(
                fn ($proc) => "{$proc['process']} ({$proc['util_1h']}%)",
                $p->cpuHighProcesses
            )).'.'
            : '';

        $evidence = $ctx->findEvidenceRegex('/^System\s+[\d.]+\s/');

        return array_merge($findings, [new FindingData(
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
        )]);
    }

    /**
     * LOG-CPU: alertas <Warn:EPM.cpu> registradas por el propio equipo
     * (proceso consumiendo % de CPU). Un hallazgo por proceso, con severidad
     * según el % máximo reportado. KB GTAC 000107097.
     */
    private function logCpuFindings(AnalysisContext $ctx): array
    {
        $rule = $ctx->rule('LOG-CPU');
        $p = $ctx->parsed;

        if ($rule === null || $p->cpuLogWarnings === []) {
            return [];
        }

        $findings = [];

        foreach ($p->cpuLogWarnings as $process => $info) {
            $level = $ctx->severityFor($rule, $info['max_pct']);
            if ($level === null) {
                continue;
            }

            $evidence = $ctx->findEvidenceRegex('/EPM\.cpu.*process\s+'.preg_quote($process, '/').'\s+consumes/');

            $findings[] = new \App\Services\Analysis\FindingData(
                ruleCode: 'LOG-CPU',
                level: $level,
                area: 'cpu_memory',
                entity: $process,
                title: "Proceso «{$process}» en alto consumo de CPU (hasta {$info['max_pct']} %)",
                description: 'El monitor de CPU del propio equipo (EPM.cpu) registró '.$info['count'].
                    " alerta(s) del proceso «{$process}» consumiendo hasta {$info['max_pct']} % de CPU ".
                    "(última: {$info['last_date']}). Este evento lo genera EXOS cuando un proceso supera ".
                    'su umbral interno de consumo sostenido.',
                impact: 'CPU alta sostenida puede retrasar protocolos de control (STP, LACP, routing) y la gestión del equipo.',
                recommendation: $process === 'hal'
                    ? 'El proceso hal en alto consumo suele asociarse a defectos conocidos o a tormentas de eventos '.
                      'de hardware (ver KB GTAC 000107097). Verificar versión de EXOS contra las release notes y, '.
                      'si persiste, abrir/escalar caso con GTAC adjuntando este archivo.'
                    : 'Identificar la causa del consumo del proceso (tormentas de broadcast, SNMP polling, telemetría) '.
                      'y consultar el catálogo de mensajes EXOS o GTAC si es recurrente.',
                evidence: $evidence['text'] ?? null,
                fileLocation: $evidence ? 'línea '.$evidence['line'].' (show log)' : 'show log',
            );
        }

        return $findings;
    }
}
