<?php

namespace App\Services\Analysis\Analyzers;

use App\Enums\FindingSeverity;
use App\Services\Analysis\AnalysisContext;
use App\Services\Analysis\AnalyzerInterface;
use App\Services\Analysis\FindingData;

/**
 * STK-RING (anillo incompleto / nodo caído) y STK-ERR (errores en puertos de stack).
 */
class StackAnalyzer implements AnalyzerInterface
{
    public function analyze(AnalysisContext $ctx): array
    {
        $p = $ctx->parsed;

        if (! $p->isStack) {
            return [];
        }

        $findings = [];

        if ($rule = $ctx->rule('STK-RING')) {
            $failedNodes = array_values(array_filter(
                $p->stackNodes,
                fn ($n) => strcasecmp($n['state'], 'Active') !== 0
            ));

            if ($failedNodes !== []) {
                $detail = implode(', ', array_map(fn ($n) => "slot {$n['slot']} ({$n['mac']}): {$n['state']}", $failedNodes));
                $findings[] = new FindingData(
                    ruleCode: 'STK-RING',
                    level: $rule->level_critical ? FindingSeverity::from($rule->level_critical) : FindingSeverity::Critical,
                    area: 'stacking',
                    entity: 'Stack',
                    title: count($failedNodes).' nodo(s) del stack fuera de estado Active',
                    description: "Nodos del stack en estado anormal: {$detail}.",
                    impact: 'Pérdida de los puertos del nodo afectado y de redundancia del stack.',
                    recommendation: 'Verificar alimentación y cables de stack del nodo; revisar logs del slot afectado.',
                    evidence: null,
                    fileLocation: 'show stacking',
                );
            } elseif ($p->stackNodes !== [] && ! $p->stackRingComplete) {
                $findings[] = new FindingData(
                    ruleCode: 'STK-RING',
                    level: FindingSeverity::from($rule->level_warning),
                    area: 'stacking',
                    entity: 'Stack',
                    title: 'Stack operando sin anillo completo (daisy-chain)',
                    description: 'La topología del stack no es un anillo cerrado. En daisy-chain, la falla de un '.
                        'enlace de stack divide el stack en dos.',
                    impact: 'Sin redundancia en los enlaces de stack.',
                    recommendation: 'Conectar el cable de stack faltante para cerrar el anillo en ventana de mantenimiento.',
                    evidence: null,
                    fileLocation: 'show stacking',
                );
            }
        }

        if (($rule = $ctx->rule('STK-ERR')) && $p->stackPortsWithErrors !== []) {
            $findings[] = new FindingData(
                ruleCode: 'STK-ERR',
                level: FindingSeverity::from($rule->level_warning),
                area: 'stacking',
                entity: implode(', ', $p->stackPortsWithErrors),
                title: 'Errores en puertos de stack',
                description: 'Los siguientes puertos de stack registran errores de recepción: '.
                    implode(', ', $p->stackPortsWithErrors).'.',
                impact: 'Errores en el backplane del stack pueden causar pérdida de tráfico entre nodos.',
                recommendation: 'Reasentar o reemplazar los cables de stack afectados en ventana de mantenimiento.',
                evidence: null,
                fileLocation: 'show port stack-ports rxerrors',
            );
        }

        return $findings;
    }
}
