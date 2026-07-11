<?php

namespace App\Services\Analysis\Analyzers;

use App\Services\Analysis\AnalysisContext;
use App\Services\Analysis\AnalyzerInterface;
use App\Services\Analysis\FindingData;

/**
 * FW-AGE: antigüedad del firmware según la fecha de compilación de la
 * imagen (show version detail), medida contra la fecha de la captura.
 */
class FirmwareAnalyzer implements AnalyzerInterface
{
    public function analyze(AnalysisContext $ctx): array
    {
        $rule = $ctx->rule('FW-AGE');
        $p = $ctx->parsed;

        if ($rule === null || $p->firmwareBuildYear === null) {
            return [];
        }

        $captureYear = $p->capturedAt !== null ? (int) $p->capturedAt->format('Y') : (int) date('Y');
        $age = max(0, $captureYear - $p->firmwareBuildYear);

        $level = $ctx->severityFor($rule, $age);
        if ($level === null) {
            return [];
        }

        $evidence = $ctx->findEvidence('ExtremeXOS version');

        return [new FindingData(
            ruleCode: 'FW-AGE',
            level: $level,
            area: 'firmware',
            entity: 'System',
            title: "Firmware con ~{$age} años de antigüedad (EXOS {$p->exosVersion})",
            description: "La imagen EXOS {$p->exosVersion} fue compilada el {$p->firmwareBuildDate} ".
                "(~{$age} años antes de esta captura). Un firmware antiguo acumula vulnerabilidades ".
                'conocidas y defectos ya corregidos en versiones posteriores.',
            impact: 'Exposición a bugs y CVEs corregidos; posible fin de soporte (verificar avisos EOL de Extreme).',
            recommendation: 'Verificar en las release notes de Extreme la última versión recomendada para este '.
                'modelo y planear la actualización en ventana de mantenimiento. Si el equipo presenta fallas, '.
                'GTAC normalmente solicita actualizar antes de escalar.',
            evidence: $evidence['text'] ?? null,
            fileLocation: $evidence ? 'línea '.$evidence['line'].' (show version)' : 'show version',
        )];
    }
}
