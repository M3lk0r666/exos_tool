<?php

namespace App\Services\Analysis\Analyzers;

use App\Enums\FindingSeverity;
use App\Services\Analysis\AnalysisContext;
use App\Services\Analysis\AnalyzerInterface;
use App\Services\Analysis\FindingData;

/**
 * SYS-REBOOT (reinicios inesperados), LOG-ERR (eventos Erro/Crit),
 * SYS-CORE (core dumps presentes).
 */
class LogsAnalyzer implements AnalyzerInterface
{
    public function analyze(AnalysisContext $ctx): array
    {
        $findings = [];
        $p = $ctx->parsed;

        // --- Reinicios inesperados ---
        if (($rule = $ctx->rule('SYS-REBOOT')) && $p->unexpectedReboots !== []) {
            $count = count($p->unexpectedReboots);
            if ($level = $ctx->severityFor($rule, $count)) {
                $evidence = $ctx->findEvidence('UnexpctRebootDtect');
                $findings[] = new FindingData(
                    ruleCode: 'SYS-REBOOT',
                    level: $level,
                    area: 'stability',
                    entity: 'System',
                    title: $count === 1 ? 'Reinicio inesperado registrado' : "{$count} fechas con reinicios inesperados",
                    description: 'El log NVRAM registra reinicios no programados ("Booting after System Failure") '.
                        'en las fechas: '.implode(', ', $p->unexpectedReboots).'. Estos eventos los reporta el '.
                        'propio switch (código EPM.UnexpctRebootDtect del catálogo EXOS).',
                    impact: 'Interrupción total del servicio durante cada reinicio.',
                    recommendation: 'Correlacionar las fechas con eventos del sitio (cortes eléctricos, UPS, trabajos). '.
                        'Si hay recurrencia sin causa externa, abrir caso con GTAC adjuntando este tech-support.',
                    evidence: $evidence['text'] ?? null,
                    fileLocation: $evidence ? 'línea '.$evidence['line'].' (show log messages nvram)' : 'show log messages nvram',
                );
            }
        }

        // --- Eventos de log con severidad Error/Critical ---
        if (($rule = $ctx->rule('LOG-ERR')) && $p->logErrors !== []) {
            $total = array_sum(array_column($p->logErrors, 'count'));
            if ($level = $ctx->severityFor($rule, $total)) {
                $top = array_slice($p->logErrors, 0, 5);
                $detail = implode("\n", array_map(
                    fn ($e) => "- [{$e['severity']}:{$e['component']}] {$e['message']} (x{$e['count']})",
                    $top
                ));
                $evidence = $ctx->findEvidence('<'.$p->logErrors[0]['severity'].':'.$p->logErrors[0]['component'].'>');
                $findings[] = new FindingData(
                    ruleCode: 'LOG-ERR',
                    level: $level,
                    area: 'stability',
                    entity: 'System',
                    title: number_format($total).' eventos de log con severidad Error/Critical',
                    description: "El log del equipo contiene eventos de severidad Error/Critical:\n{$detail}".
                        (count($p->logErrors) > 5 ? "\n… y ".(count($p->logErrors) - 5).' mensajes distintos más.' : ''),
                    impact: 'Depende del componente afectado; revisar el detalle de cada mensaje.',
                    recommendation: 'Revisar cada componente reportado. Si el mensaje es recurrente y no se explica '.
                        'por la operación normal, consultar el catálogo de mensajes EXOS o abrir caso con GTAC.',
                    evidence: $evidence['text'] ?? null,
                    fileLocation: $evidence ? 'línea '.$evidence['line'].' (show log)' : 'show log',
                );
            }
        }

        // --- Core dumps ---
        if (($rule = $ctx->rule('SYS-CORE')) && $p->coreDumps !== []) {
            $findings[] = new FindingData(
                ruleCode: 'SYS-CORE',
                level: FindingSeverity::from($rule->level_warning),
                area: 'stability',
                entity: 'System',
                title: 'Core dumps presentes en el equipo',
                description: 'Se detectaron volcados de memoria (core dumps) en: '.implode('; ', $p->coreDumps).'. '.
                    'Un core dump indica que un proceso del sistema operativo falló.',
                impact: 'Evidencia de fallas de software; el proceso afectado pudo reiniciarse con pérdida temporal de su función.',
                recommendation: 'No eliminar los archivos. Abrir caso con GTAC adjuntando el tech-support y los core dumps.',
                evidence: null,
                fileLocation: 'show debug system-dump',
            );
        }

        return $findings;
    }
}
