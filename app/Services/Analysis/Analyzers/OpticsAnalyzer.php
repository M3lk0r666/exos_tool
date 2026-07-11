<?php

namespace App\Services\Analysis\Analyzers;

use App\Enums\FindingSeverity;
use App\Services\Analysis\AnalysisContext;
use App\Services\Analysis\AnalyzerInterface;
use App\Services\Analysis\FindingData;

/**
 * OPT-CFG: conflictos de configuración de óptica (HAL.Port.OpticCfgCflct).
 * Nota de dominio: óptica SX (1G) en puerto forzado a "auto off speed 10000"
 * genera este conflicto; la corrección típica es "auto on speed 1000".
 */
class OpticsAnalyzer implements AnalyzerInterface
{
    public function analyze(AnalysisContext $ctx): array
    {
        $rule = $ctx->rule('OPT-CFG');
        $p = $ctx->parsed;

        if ($rule === null || $p->opticConflicts === []) {
            return [];
        }

        $findings = [];

        foreach ($p->opticConflicts as $message) {
            $port = preg_match('/Port (\d+(?::\d+)?)/', $message, $m) ? $m[1] : null;
            $forced = $port !== null && isset($p->forcedSpeedPorts[$port])
                ? " El puerto está forzado a {$p->forcedSpeedPorts[$port]} Mbps en la configuración."
                : '';

            $evidence = $ctx->findEvidence('OpticCfgCflct');

            $findings[] = new FindingData(
                ruleCode: 'OPT-CFG',
                level: FindingSeverity::from($rule->level_warning),
                area: 'ports',
                entity: $port,
                title: 'Conflicto de configuración de óptica'.($port ? " en el puerto {$port}" : ''),
                description: "El log reporta conflicto óptica/configuración: \"{$message}\".{$forced} ".
                    'Típicamente una óptica SX (1G) en un puerto configurado a 10G.',
                impact: 'El enlace puede no levantar o quedar inestable.',
                recommendation: $port
                    ? "Ajustar la configuración a la velocidad de la óptica instalada (p. ej. \"configure ports {$port} auto on speed 1000\") en ventana de mantenimiento."
                    : 'Ajustar la velocidad configurada a la de la óptica instalada en ventana de mantenimiento.',
                evidence: $evidence['text'] ?? null,
                fileLocation: $evidence ? 'línea '.$evidence['line'].' (show log)' : 'show log',
            );
        }

        return $findings;
    }
}
