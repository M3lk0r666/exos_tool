<?php

namespace App\Services\Analysis\Analyzers;

use App\Services\Analysis\AnalysisContext;
use App\Services\Analysis\AnalyzerInterface;
use App\Services\Analysis\FindingData;

/**
 * SEC-AUTH: intentos de autenticación fallidos (AAA.authFail).
 */
class SecurityAnalyzer implements AnalyzerInterface
{
    public function analyze(AnalysisContext $ctx): array
    {
        $rule = $ctx->rule('SEC-AUTH');
        $p = $ctx->parsed;

        if ($rule === null || $p->authFailures === 0) {
            return [];
        }

        $level = $ctx->severityFor($rule, $p->authFailures);
        if ($level === null) {
            return [];
        }

        $evidence = $ctx->findEvidence('authFail');

        return [new FindingData(
            ruleCode: 'SEC-AUTH',
            level: $level,
            area: 'security',
            entity: 'System',
            title: "{$p->authFailures} intentos de login fallidos registrados",
            description: "El log registra {$p->authFailures} eventos AAA.authFail (intentos de autenticación fallidos).",
            impact: 'Puede indicar credenciales olvidadas, scripts desactualizados o intentos de acceso no autorizado.',
            recommendation: 'Revisar el origen de los intentos (usuario/IP en el log). Si no se reconocen, '.
                'restringir el acceso de gestión (ACL) y rotar credenciales.',
            evidence: $evidence['text'] ?? null,
            fileLocation: $evidence ? 'línea '.$evidence['line'].' (show log)' : 'show log',
        )];
    }
}
