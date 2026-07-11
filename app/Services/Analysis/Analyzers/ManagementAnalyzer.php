<?php

namespace App\Services\Analysis\Analyzers;

use App\Enums\FindingSeverity;
use App\Services\Analysis\AnalysisContext;
use App\Services\Analysis\AnalyzerInterface;
use App\Services\Analysis\FindingData;

/**
 * MGMT-SEC: buenas prácticas de gestión — SSH deshabilitado/llave inválida,
 * Telnet habilitado, SNMP deshabilitado, sin NTP/SNTP.
 */
class ManagementAnalyzer implements AnalyzerInterface
{
    public function analyze(AnalysisContext $ctx): array
    {
        $rule = $ctx->rule('MGMT-SEC');
        if ($rule === null) {
            return [];
        }

        $p = $ctx->parsed;
        $issues = [];

        if ($p->sshStatus !== null && stripos($p->sshStatus, 'Enabled') !== 0) {
            $issues[] = "SSH no está operativo ({$p->sshStatus}); la gestión remota segura no está disponible.";
        }

        if ($p->telnetStatus !== null && stripos($p->telnetStatus, 'Enabled') === 0) {
            $issues[] = 'Telnet está habilitado; transmite credenciales en texto claro.';
        }

        if ($p->snmpDisabled) {
            $issues[] = 'SNMP está deshabilitado; el equipo no puede integrarse a plataformas de monitoreo.';
        }

        if (! $p->ntpConfigured) {
            $issues[] = 'No hay NTP/SNTP configurado; los timestamps de logs no son confiables para correlación de eventos.';
        }

        if ($issues === []) {
            return [];
        }

        return [new FindingData(
            ruleCode: 'MGMT-SEC',
            level: FindingSeverity::from($rule->level_warning),
            area: 'management',
            entity: 'System',
            title: count($issues).' desviación(es) de buenas prácticas de gestión',
            description: "Revisión de gestión y monitoreo:\n- ".implode("\n- ", $issues),
            impact: 'Dificulta la gestión segura, el monitoreo proactivo y el análisis forense de eventos.',
            recommendation: 'Habilitar SSH (regenerar llave si es inválida), deshabilitar Telnet, habilitar SNMP '.
                'hacia la plataforma de monitoreo y configurar NTP. Aplicar en ventana de mantenimiento.',
            evidence: null,
            fileLocation: 'show management / show configuration',
        )];
    }
}
