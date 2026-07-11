<?php

namespace App\Services\Analysis;

use App\Enums\FindingSeverity;

/**
 * Motor de correlación de eventos (Anexo B, correlaciones iniciales):
 *  (a) Reinicios inesperados sin core dumps ⇒ sugerir causa eléctrica externa.
 *  (b) CRC + fragmentos (+ jabber) en el mismo puerto ⇒ capa física dañada.
 *  (c) Flapping + negociación a 10 Mbps en el mismo puerto ⇒ cableado defectuoso.
 *  (d) Firmware antiguo + reinicios inesperados ⇒ actualizar antes de escalar a TAC.
 *
 * Las correlaciones generan hallazgos adicionales que unifican el diagnóstico;
 * no sustituyen a los hallazgos individuales que las sustentan.
 */
class CorrelationEngine
{
    /**
     * @param  array<int, FindingData>  $findings
     * @return array<int, FindingData>
     */
    public function correlate(AnalysisContext $ctx, array $findings): array
    {
        $correlated = [];
        $p = $ctx->parsed;

        $byRule = [];
        foreach ($findings as $f) {
            $byRule[$f->ruleCode][] = $f;
        }

        $portsOf = fn (string $rule) => array_filter(array_map(
            fn (FindingData $f) => $f->entity,
            $byRule[$rule] ?? []
        ));

        // --- (a) Reinicios sin core dumps ⇒ posible causa eléctrica ---
        if (isset($byRule['SYS-REBOOT']) && ! isset($byRule['SYS-CORE'])) {
            $scope = $p->isStack
                ? 'El equipo es un stack y los reinicios aparecen sin volcados de memoria en ningún slot'
                : 'Los reinicios aparecen sin volcados de memoria';

            $correlated[] = new FindingData(
                ruleCode: 'CORR-ELEC',
                level: FindingSeverity::Medium,
                area: 'stability',
                entity: 'System',
                title: 'Patrón compatible con falla eléctrica externa',
                description: "{$scope} (core dumps). Cuando un reinicio es causado por software, el sistema ".
                    'normalmente deja core dumps; su ausencia con reinicios "Booting after System Failure" '.
                    'sugiere pérdida de alimentación externa (acometida, UPS, PDU) más que un defecto del equipo.',
                impact: 'Mientras persista la causa eléctrica, los reinicios seguirán ocurriendo.',
                recommendation: 'Revisar la infraestructura eléctrica del sitio: UPS, contactos, PDU y calidad de '.
                    'la acometida, correlacionando con las fechas de los reinicios.',
                evidence: 'Fechas de reinicio: '.implode(', ', $p->unexpectedReboots),
                fileLocation: 'show log messages nvram + show debug system-dump',
            );
        }

        // --- (b) CRC + fragmentos en el mismo puerto ⇒ capa física ---
        $physicalPorts = array_intersect($portsOf('PORT-CRC'), $portsOf('PORT-FRAG'));
        foreach ($physicalPorts as $port) {
            $err = $p->rxErrors[$port] ?? null;
            $jabber = $err && $err['jabber'] > 0 ? ' y jabber ('.number_format($err['jabber']).')' : '';

            $correlated[] = new FindingData(
                ruleCode: 'CORR-PHY',
                level: FindingSeverity::High,
                area: 'ports',
                entity: (string) $port,
                title: "Capa física dañada en el puerto {$port} (diagnóstico correlacionado)",
                description: "El puerto {$port} combina errores CRC (".number_format($err['crc'] ?? 0).
                    '), fragmentos ('.number_format($err['frag'] ?? 0)."){$jabber}. La coincidencia de estos ".
                    'contadores en el mismo puerto apunta a daño físico: cable, conector o NIC del extremo remoto.',
                impact: 'Degradación severa y sostenida del enlace.',
                recommendation: 'Certificar/reemplazar el cableado del puerto y revisar la NIC del dispositivo '.
                    'conectado. Este diagnóstico combina múltiples indicadores independientes.',
                evidence: $err ? "CRC={$err['crc']}, fragmentos={$err['frag']}, jabber={$err['jabber']}, align={$err['align']}" : null,
                fileLocation: 'show ports rxerrors',
            );
        }

        // --- (c) Flapping + 10 Mbps en el mismo puerto ⇒ cableado defectuoso ---
        $cablePorts = array_intersect($portsOf('PORT-FLAP'), $portsOf('PORT-10M'));
        foreach ($cablePorts as $port) {
            $correlated[] = new FindingData(
                ruleCode: 'CORR-CABLE',
                level: FindingSeverity::High,
                area: 'ports',
                entity: (string) $port,
                title: "Cableado defectuoso en el puerto {$port} (diagnóstico correlacionado)",
                description: "El puerto {$port} presenta flapping (".number_format($p->portLinkTransitions[$port] ?? 0).
                    ' transiciones de link) y además negoció a 10 Mbps. La combinación indica un par del cable '.
                    'dañado: el enlace cae y renegocia a la velocidad mínima que los pares sanos permiten.',
                impact: 'Enlace inestable y lento para el dispositivo conectado.',
                recommendation: 'Reemplazar el cable del puerto (no solo reasentarlo) y certificar el tendido.',
                evidence: null,
                fileLocation: 'show ports information + show log',
            );
        }

        // --- (d) Firmware antiguo + reinicios ⇒ actualizar antes de TAC ---
        $fwFinding = $byRule['FW-AGE'][0] ?? null;
        if ($fwFinding !== null
            && isset($byRule['SYS-REBOOT'])
            && in_array($fwFinding->level, [FindingSeverity::High, FindingSeverity::Critical], true)) {
            $correlated[] = new FindingData(
                ruleCode: 'CORR-FW',
                level: FindingSeverity::High,
                area: 'firmware',
                entity: 'System',
                title: 'Reinicios inesperados sobre firmware antiguo: actualizar antes de escalar',
                description: 'El equipo presenta reinicios inesperados ejecutando una imagen EXOS con varios años '.
                    'de antigüedad. Muchos defectos que causan reinicios ya están corregidos en versiones '.
                    'posteriores, y GTAC normalmente solicita actualizar antes de profundizar el diagnóstico.',
                impact: 'El diagnóstico de la causa raíz se dificulta sobre una versión con defectos conocidos.',
                recommendation: 'Planear la actualización de EXOS a la versión recomendada por Extreme para este '.
                    'modelo antes de abrir o escalar un caso con TAC.',
                evidence: null,
                fileLocation: 'show version + show log messages nvram',
            );
        }

        return $correlated;
    }
}
