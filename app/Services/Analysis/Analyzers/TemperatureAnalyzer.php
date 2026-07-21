<?php

namespace App\Services\Analysis\Analyzers;

use App\Enums\FindingSeverity;
use App\Services\Analysis\AnalysisContext;
use App\Services\Analysis\AnalyzerInterface;
use App\Services\Analysis\FindingData;

/**
 * ENV-TEMP. Prioriza los límites del propio equipo (Anexo D, punto 4):
 * - Estado ≠ Normal: el switch mismo declara el problema → Critical.
 * - Margen respecto al máximo de fábrica ≤ umbral configurable → advertencia.
 */
class TemperatureAnalyzer implements AnalyzerInterface
{
    public function analyze(AnalysisContext $ctx): array
    {
        $rule = $ctx->rule('ENV-TEMP');
        if ($rule === null) {
            return [];
        }

        $findings = [];
        $margin = (float) ($rule->threshold_warning ?? 15);

        foreach ($ctx->parsed->temperatures as $t) {
            $evidence = $ctx->findEvidenceRegex('/^\s*'.preg_quote($t['unit'], '/').'\s*:.*'.preg_quote((string) $t['temp'], '/').'/');

            // El propio equipo declara estado fuera de Normal → hecho, no interpretación.
            if (strcasecmp($t['status'], 'Normal') !== 0) {
                $findings[] = new FindingData(
                    ruleCode: 'ENV-TEMP',
                    level: $rule->level_critical ? FindingSeverity::from($rule->level_critical) : FindingSeverity::Critical,
                    area: 'environment',
                    entity: $t['unit'],
                    title: "Temperatura fuera de rango en {$t['unit']} ({$t['status']})",
                    description: "El equipo reporta estado \"{$t['status']}\" en {$t['unit']}: {$t['temp']} °C ".
                        "(rango de fábrica: máx. {$t['max']} °C). Este estado lo evalúa el propio switch contra sus límites.",
                    impact: 'Riesgo de apagado térmico o daño permanente al hardware.',
                    recommendation: 'Atención inmediata: revisar climatización del sitio, flujo de aire y ventiladores.',
                    evidence: $evidence['text'] ?? null,
                    fileLocation: $evidence ? 'línea '.$evidence['line'].' (show temperature)' : 'show temperature',
                );

                continue;
            }

            // Margen contra el TOPE DEL RANGO NORMAL del modelo (columna
            // "Normal" de show temperature, p. ej. 10-100). El "Max" es el
            // límite de apagado térmico y solo se usa como respaldo si el
            // formato no incluyó el rango normal.
            $normalMax = $t['normal_max'] ?? $t['max'];
            $remaining = $normalMax - $t['temp'];

            // Guardia proporcional: el margen en °C no escala igual en rangos
            // angostos (sensor de aire 0-40) que amplios (sensor de CPU 10-100).
            // Solo se advierte si además la lectura superó el 75 % del rango
            // normal del modelo.
            $inUpperQuarter = true;
            if (isset($t['normal_min'], $t['normal_max'])
                && $t['normal_min'] !== null && $t['normal_max'] !== null) {
                $range = $t['normal_max'] - $t['normal_min'];
                $inUpperQuarter = $range <= 0 || $t['temp'] >= $t['normal_min'] + 0.75 * $range;
            }

            if ($remaining <= $margin && $inUpperQuarter) {
                $findings[] = new FindingData(
                    ruleCode: 'ENV-TEMP',
                    level: FindingSeverity::from($rule->level_warning),
                    area: 'environment',
                    entity: $t['unit'],
                    title: "Temperatura de {$t['unit']} cerca del límite normal ({$t['temp']} °C)",
                    description: "{$t['unit']} opera a {$t['temp']} °C, a solo ".round($remaining, 1).
                        " °C del tope del rango normal de su modelo ({$normalMax} °C según el propio equipo; ".
                        "apagado térmico: {$t['max']} °C). El estado aún es Normal, pero el margen es reducido.",
                    impact: 'Sin margen ante un aumento de temperatura ambiente o falla de climatización.',
                    recommendation: 'Revisar climatización y ventilación del gabinete de forma preventiva.',
                    evidence: $evidence['text'] ?? null,
                    fileLocation: $evidence ? 'línea '.$evidence['line'].' (show temperature)' : 'show temperature',
                );
            }
        }

        return $findings;
    }
}
