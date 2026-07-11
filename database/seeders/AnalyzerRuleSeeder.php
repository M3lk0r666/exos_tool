<?php

namespace Database\Seeders;

use App\Models\AnalyzerRule;
use Illuminate\Database\Seeder;

/**
 * Reglas y umbrales iniciales del Anexo B del prompt maestro.
 * Editables desde UI en Fase 7; los analyzers las leen de BD.
 */
class AnalyzerRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            ['code' => 'PORT-CRC', 'analyzer' => 'PortsAnalyzer', 'description' => 'Errores CRC acumulados por puerto', 'threshold_warning' => 100, 'threshold_critical' => 10000, 'level_warning' => 'medium', 'level_critical' => 'high'],
            ['code' => 'PORT-FRAG', 'analyzer' => 'PortsAnalyzer', 'description' => 'Fragmentos por puerto', 'threshold_warning' => null, 'threshold_critical' => 10000, 'level_warning' => 'medium', 'level_critical' => 'high'],
            ['code' => 'PORT-OVERSIZE', 'analyzer' => 'PortsAnalyzer', 'description' => 'Tramas oversize (posible MTU mismatch)', 'threshold_warning' => 10000, 'threshold_critical' => null, 'level_warning' => 'medium', 'level_critical' => null],
            ['code' => 'PORT-FLAP', 'analyzer' => 'PortsAnalyzer', 'description' => 'Transiciones de link acumuladas (flapping)', 'threshold_warning' => 1000, 'threshold_critical' => 10000, 'level_warning' => 'medium', 'level_critical' => 'high'],
            ['code' => 'PORT-10M', 'analyzer' => 'PortsAnalyzer', 'description' => 'Puerto negociando a 10 Mbps en red GbE (par de cable dañado)', 'threshold_warning' => null, 'threshold_critical' => null, 'level_warning' => 'medium', 'level_critical' => null],
            ['code' => 'PORT-CONG', 'analyzer' => 'PortsAnalyzer', 'description' => 'Congestión activa (descartes en el último segundo)', 'threshold_warning' => 1, 'threshold_critical' => null, 'level_warning' => 'medium', 'level_critical' => null],
            ['code' => 'SYS-REBOOT', 'analyzer' => 'LogsAnalyzer', 'description' => 'Reinicios inesperados registrados (Booting after System Failure)', 'threshold_warning' => 1, 'threshold_critical' => 2, 'level_warning' => 'medium', 'level_critical' => 'critical'],
            ['code' => 'LOG-ERR', 'analyzer' => 'LogsAnalyzer', 'description' => 'Eventos de log con severidad Error/Critical', 'threshold_warning' => 1, 'threshold_critical' => 5, 'level_warning' => 'medium', 'level_critical' => 'high'],
            ['code' => 'SYS-CORE', 'analyzer' => 'LogsAnalyzer', 'description' => 'Core dumps presentes en slots', 'threshold_warning' => 1, 'threshold_critical' => null, 'level_warning' => 'medium', 'level_critical' => null],
            ['code' => 'CPU-1H', 'analyzer' => 'CpuAnalyzer', 'description' => 'Utilización de CPU del sistema promedio 1 h (%)', 'threshold_warning' => 40, 'threshold_critical' => 70, 'level_warning' => 'medium', 'level_critical' => 'high'],
            ['code' => 'MEM-FREE', 'analyzer' => 'MemoryAnalyzer', 'description' => 'Memoria libre (%) — umbral hacia abajo', 'threshold_warning' => 20, 'threshold_critical' => 10, 'level_warning' => 'medium', 'level_critical' => 'high', 'params' => ['direction' => 'below']],
            ['code' => 'ENV-TEMP', 'analyzer' => 'TemperatureAnalyzer', 'description' => 'Temperatura: margen (°C) respecto al máximo; estado ≠ Normal es crítico', 'threshold_warning' => 15, 'threshold_critical' => null, 'level_warning' => 'medium', 'level_critical' => 'critical', 'params' => ['margin_mode' => true]],
            ['code' => 'ENV-FAN', 'analyzer' => 'FanAnalyzer', 'description' => 'Ventilador en falla', 'threshold_warning' => null, 'threshold_critical' => 1, 'level_warning' => 'medium', 'level_critical' => 'critical'],
            ['code' => 'PWR-PSU', 'analyzer' => 'PowerAnalyzer', 'description' => 'Fuente de poder en falla', 'threshold_warning' => null, 'threshold_critical' => 1, 'level_warning' => 'medium', 'level_critical' => 'critical'],
            ['code' => 'FW-AGE', 'analyzer' => 'FirmwareAnalyzer', 'description' => 'Antigüedad del firmware (años desde compilación de la imagen)', 'threshold_warning' => 2, 'threshold_critical' => 5, 'level_warning' => 'medium', 'level_critical' => 'high'],
            ['code' => 'HW-AGE', 'analyzer' => 'HardwareAnalyzer', 'description' => 'Edad del hardware según odómetro (años)', 'threshold_warning' => 7, 'threshold_critical' => null, 'level_warning' => 'medium', 'level_critical' => null],
            ['code' => 'STK-RING', 'analyzer' => 'StackAnalyzer', 'description' => 'Stack sin anillo completo; nodo caído es crítico', 'threshold_warning' => null, 'threshold_critical' => null, 'level_warning' => 'medium', 'level_critical' => 'critical'],
            ['code' => 'STK-ERR', 'analyzer' => 'StackAnalyzer', 'description' => 'Errores en puertos de stack', 'threshold_warning' => 1, 'threshold_critical' => null, 'level_warning' => 'medium', 'level_critical' => null],
            ['code' => 'OPT-CFG', 'analyzer' => 'OpticsAnalyzer', 'description' => 'Conflicto de configuración de óptica (OpticCfgCflct)', 'threshold_warning' => null, 'threshold_critical' => null, 'level_warning' => 'medium', 'level_critical' => null],
            ['code' => 'MGMT-SEC', 'analyzer' => 'ManagementAnalyzer', 'description' => 'SSH/SNMP deshabilitados o sin NTP configurado', 'threshold_warning' => null, 'threshold_critical' => null, 'level_warning' => 'medium', 'level_critical' => null],
            ['code' => 'SEC-AUTH', 'analyzer' => 'SecurityAnalyzer', 'description' => 'Intentos de login fallidos (authFail)', 'threshold_warning' => 5, 'threshold_critical' => null, 'level_warning' => 'informational', 'level_critical' => null],
        ];

        // Trazabilidad de umbrales (Anexo D, punto 1): origen documentado de cada regla.
        $references = [
            'PORT-CRC' => 'IEEE 802.3 (BER objetivo ~1e-12: CRC sostenidos ≈ 0 en enlace sano); umbral escalonado por práctica de ingeniería',
            'PORT-FRAG' => 'IEEE 802.3: fragmentos indican colisiones tardías/cableado; umbral por práctica de ingeniería',
            'PORT-OVERSIZE' => 'Mismatch de MTU/jumbo frames entre extremos; umbral por práctica de ingeniería',
            'PORT-FLAP' => 'Transiciones acumuladas desde boot (show ports information); umbral por práctica de ingeniería',
            'PORT-10M' => 'Nota de dominio validada: negociación a 10 Mbps en red GbE indica par de cable dañado (KB GTAC)',
            'PORT-CONG' => 'Drops del último segundo = congestión activa al momento de la captura (sección Port Congestion)',
            'SYS-REBOOT' => 'Evento EPM.UnexpctRebootDtect del catálogo de mensajes EXOS (hecho reportado por el equipo)',
            'LOG-ERR' => 'Severidades Erro/Crit del catálogo de mensajes EXOS; umbral por práctica de ingeniería',
            'SYS-CORE' => 'Presencia de core dumps (show debug system-dump); hecho reportado por el equipo',
            'CPU-1H' => 'Columna 1h de show cpu-monitoring; NO usar load average (normal ~7 en EXOS). Umbral por práctica de ingeniería',
            'MEM-FREE' => 'Memoria libre de show memory; umbral por práctica de ingeniería',
            'ENV-TEMP' => 'Límites min/normal/max de fábrica reportados por el propio equipo (show temperature); margen configurable',
            'ENV-FAN' => 'Estado declarado por el propio equipo (show fans); cualquier falla es crítica',
            'PWR-PSU' => 'Estado declarado por el propio equipo (show power); cualquier falla es crítica',
            'FW-AGE' => 'Fecha de compilación de la imagen (show version); contrastar con release notes y avisos EOL de Extreme',
            'HW-AGE' => 'Odómetro del equipo (show odometers); contrastar con avisos End of Sale/Support de Extreme',
            'STK-RING' => 'Topología reportada por show stacking; anillo incompleto pierde redundancia (documentación SummitStack)',
            'STK-ERR' => 'Errores rx en puertos de stack (show port stack-ports rxerrors)',
            'OPT-CFG' => 'Evento HAL.Port.OpticCfgCflct del catálogo EXOS; caso típico: óptica SX 1G en puerto forzado a 10G (KB GTAC)',
            'MGMT-SEC' => 'Buenas prácticas de gestión: SSH/SNMP/NTP (guías de hardening de Extreme)',
            'SEC-AUTH' => 'Eventos AAA.authFail del catálogo EXOS; informativo salvo patrón de ataque',
        ];

        foreach ($rules as $rule) {
            $rule['params'] = ($rule['params'] ?? [])
                + ['reference' => $references[$rule['code']] ?? null];

            AnalyzerRule::updateOrCreate(
                ['code' => $rule['code']],
                $rule + ['enabled' => true]
            );
        }
    }
}
