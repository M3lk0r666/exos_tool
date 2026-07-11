<?php

namespace App\Services\Analysis\Analyzers;

use App\Services\Analysis\AnalysisContext;
use App\Services\Analysis\AnalyzerInterface;
use App\Services\Analysis\FindingData;

/**
 * PORT-CRC, PORT-FRAG, PORT-OVERSIZE, PORT-FLAP, PORT-10M, PORT-CONG.
 * Nota de dominio: los contadores son acumulados desde el último boot.
 */
class PortsAnalyzer implements AnalyzerInterface
{
    public function analyze(AnalysisContext $ctx): array
    {
        $findings = [];
        $p = $ctx->parsed;

        // --- Errores físicos por puerto (rxerrors) ---
        foreach ($p->rxErrors as $port => $err) {
            if ($rule = $ctx->rule('PORT-CRC')) {
                if ($level = $ctx->severityFor($rule, $err['crc'])) {
                    // Ancla con el valor de CRC para tomar la fila de rxerrors
                    // (la tabla de txerrors tiene la misma forma).
                    $evidence = $ctx->findEvidenceRegex('/^'.preg_quote((string) $port, '/').'\s+\w+\s+'.$err['crc'].'\s/');
                    $findings[] = new FindingData(
                        ruleCode: 'PORT-CRC',
                        level: $level,
                        area: 'ports',
                        entity: (string) $port,
                        title: "Errores CRC en el puerto {$port}",
                        description: 'El puerto '.$port.' acumula '.number_format($err['crc']).
                            ' errores CRC desde el último arranque (uptime: '.($p->uptimeText ?? 'n/d').'). '.
                            'En un enlace Ethernet sano los CRC deben ser prácticamente cero; un contador '.
                            'que crece de forma sostenida indica daño en capa física.',
                        impact: 'Retransmisiones y degradación de rendimiento para los dispositivos conectados a este puerto.',
                        recommendation: 'Verificar en ventana de mantenimiento: cable, conectores y NIC del extremo remoto. '.
                            'Comparar contra la siguiente captura para confirmar si el contador sigue creciendo.',
                        evidence: $evidence['text'] ?? null,
                        fileLocation: $evidence ? 'línea '.$evidence['line'].' (show ports rxerrors)' : 'show ports rxerrors',
                    );
                }
            }

            if ($rule = $ctx->rule('PORT-FRAG')) {
                if ($level = $ctx->severityFor($rule, $err['frag'])) {
                    $evidence = $ctx->findEvidenceRegex('/^'.preg_quote((string) $port, '/').'\s+\w+\s+'.$err['crc'].'\s/');
                    $findings[] = new FindingData(
                        ruleCode: 'PORT-FRAG',
                        level: $level,
                        area: 'ports',
                        entity: (string) $port,
                        title: "Fragmentos en el puerto {$port}",
                        description: 'El puerto '.$port.' acumula '.number_format($err['frag']).
                            ' tramas fragmentadas (menores a 64 bytes con CRC inválido), típico de colisiones '.
                            'tardías o cableado defectuoso.',
                        impact: 'Pérdida de tramas y retransmisiones en el segmento.',
                        recommendation: 'Revisar cableado y duplex del enlace en ventana de mantenimiento.',
                        evidence: $evidence['text'] ?? null,
                        fileLocation: $evidence ? 'línea '.$evidence['line'].' (show ports rxerrors)' : 'show ports rxerrors',
                    );
                }
            }

            if ($rule = $ctx->rule('PORT-OVERSIZE')) {
                if ($level = $ctx->severityFor($rule, $err['over'])) {
                    $findings[] = new FindingData(
                        ruleCode: 'PORT-OVERSIZE',
                        level: $level,
                        area: 'ports',
                        entity: (string) $port,
                        title: "Tramas oversize en el puerto {$port}",
                        description: 'El puerto '.$port.' registra '.number_format($err['over']).
                            ' tramas oversize, generalmente por diferencia de MTU/jumbo frames entre extremos.',
                        impact: 'Posible descarte de tramas grandes según configuración.',
                        recommendation: 'Homologar MTU/jumbo frames en ambos extremos del enlace.',
                        evidence: null,
                        fileLocation: 'show ports rxerrors',
                    );
                }
            }
        }

        // --- Flapping (transiciones de link acumuladas) ---
        if ($rule = $ctx->rule('PORT-FLAP')) {
            foreach ($p->portLinkTransitions as $port => $transitions) {
                if ($level = $ctx->severityFor($rule, $transitions)) {
                    $evidence = $ctx->findEvidenceRegex('/^'.preg_quote((string) $port, '/').'\s+\S+\s+\w+\s+-\s*\/\s*-\s*\d+/');
                    $findings[] = new FindingData(
                        ruleCode: 'PORT-FLAP',
                        level: $level,
                        area: 'ports',
                        entity: (string) $port,
                        title: "Flapping en el puerto {$port}",
                        description: 'El puerto '.$port.' acumula '.number_format($transitions).
                            ' transiciones de link desde el último arranque, lo que indica un enlace inestable.',
                        impact: 'Reconvergencia constante (STP/routing), microcortes para el dispositivo conectado.',
                        recommendation: 'Verificar cable, conector y equipo remoto. Si el dispositivo final se '.
                            'enciende/apaga con frecuencia (p. ej. equipos de escritorio), documentarlo como esperado.',
                        evidence: $evidence['text'] ?? null,
                        fileLocation: $evidence ? 'línea '.$evidence['line'].' (show ports information)' : 'show ports information',
                    );
                }
            }
        }

        // --- Puertos que negociaron a 10 Mbps ---
        if (($rule = $ctx->rule('PORT-10M')) && $p->portsAt10Mbps !== []) {
            foreach ($p->portsAt10Mbps as $port) {
                $evidence = $ctx->findEvidence("Port {$port} link UP at speed 10 Mbps");
                $findings[] = new FindingData(
                    ruleCode: 'PORT-10M',
                    level: \App\Enums\FindingSeverity::from($rule->level_warning),
                    area: 'ports',
                    entity: (string) $port,
                    title: "Puerto {$port} negoció a 10 Mbps",
                    description: 'El log registra que el puerto '.$port.' negoció a 10 Mbps en una red gigabit. '.
                        'Esto casi siempre indica un par del cable dañado (la negociación cae al mínimo común).',
                    impact: 'Rendimiento severamente degradado para el dispositivo conectado.',
                    recommendation: 'Certificar o reemplazar el cableado del puerto en ventana de mantenimiento.',
                    evidence: $evidence['text'] ?? null,
                    fileLocation: $evidence ? 'línea '.$evidence['line'].' (show log)' : 'show log',
                );
            }
        }

        // --- Congestión activa (drops en el último segundo) ---
        if ($rule = $ctx->rule('PORT-CONG')) {
            foreach ($p->congestion as $port => $cong) {
                if ($cong['last_second'] > 0 && $ctx->severityFor($rule, $cong['last_second'])) {
                    $findings[] = new FindingData(
                        ruleCode: 'PORT-CONG',
                        level: \App\Enums\FindingSeverity::from($rule->level_warning),
                        area: 'ports',
                        entity: (string) $port,
                        title: "Congestión activa en el puerto {$port}",
                        description: 'El puerto '.$port.' descartó '.number_format($cong['last_second']).
                            ' paquetes en el último segundo al momento de la captura (acumulado: '.
                            number_format($cong['drops']).'). La congestión estaba ocurriendo en ese instante.',
                        impact: 'Pérdida de paquetes activa; afecta aplicaciones sensibles (voz/video).',
                        recommendation: 'Revisar el patrón de tráfico del puerto y evaluar QoS o incremento de capacidad.',
                        evidence: null,
                        fileLocation: 'Port Congestion',
                    );
                }
            }
        }

        return $findings;
    }
}
