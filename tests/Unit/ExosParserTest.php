<?php

namespace Tests\Unit;

use App\Services\Parser\ExosParser;
use App\Services\Parser\ParsedTechSupport;
use PHPUnit\Framework\TestCase;

/**
 * Tests golden-file del parser sobre archivos tech-support reales
 * (ejemplos-tech/, anonimizados). Los valores esperados fueron verificados
 * contra el contenido de los archivos y contra el script Python de
 * referencia (scripts/exos_health_report.py).
 */
class ExosParserTest extends TestCase
{
    private static ParsedTechSupport $standard;

    private static ParsedTechSupport $stack;

    public static function setUpBeforeClass(): void
    {
        $parser = new ExosParser;
        self::$standard = $parser->parse(
            file_get_contents(__DIR__.'/../Fixtures/techsupport_standard_x440g2_exos22.txt')
        );
        self::$stack = $parser->parse(
            file_get_contents(__DIR__.'/../Fixtures/techsupport_stack_x460_exos12.txt')
        );
    }

    // ------------------------------------------------------------------
    // Standalone X440G2, EXOS 22.7 (delimitador "->comando", filtros | exclude)
    // ------------------------------------------------------------------

    public function test_standard_identity(): void
    {
        $p = self::$standard;

        $this->assertSame('SWITCH-PB-Z4-Auditorio', $p->sysName);
        $this->assertSame('00:04:96:B4:44:65', $p->systemMac);
        $this->assertSame('X440G2-48p-10G4', $p->systemType);
        $this->assertFalse($p->isStack);
        $this->assertSame(71, $p->bootCount);
        // Current Time del archivo, no fecha de subida
        $this->assertSame('2026-07-02 19:39:18', $p->capturedAt?->format('Y-m-d H:i:s'));
        // 542 días 2 h 37 m 42 s
        $this->assertSame(46838262, $p->uptimeSeconds);
    }

    public function test_standard_firmware(): void
    {
        $p = self::$standard;

        $this->assertSame('22.7.1.2', $p->exosVersion);
        $this->assertSame(2019, $p->firmwareBuildYear);
        $this->assertSame('1.0.1.8', $p->bootRom);
        $this->assertSame('Edge', $p->licenseLevel);

        // Serial standalone: segundo bloque de "Switch : <parte> <serie> Rev ..."
        $this->assertSame(['Switch' => '1910N-44546'], $p->serialNumbers);
        $this->assertSame('800618-00-18', $p->partNumbers['Switch']);
    }

    public function test_standard_environment(): void
    {
        $p = self::$standard;

        $this->assertCount(1, $p->temperatures);
        $this->assertSame(61.5, $p->temperatures[0]['temp']);
        $this->assertSame('Normal', $p->temperatures[0]['status']);
        $this->assertSame(110.0, $p->temperatures[0]['max']);
        // Rango normal del modelo (columna "Normal": 10-100)
        $this->assertSame(100.0, $p->temperatures[0]['normal_max']);
        $this->assertSame(10.0, $p->temperatures[0]['normal_min']);

        $this->assertSame(4, $p->fansOk);
        $this->assertSame([], $p->fansFailed);

        $this->assertSame(1, $p->psuOn);
        $this->assertSame(0, $p->psuFailed);
        $this->assertSame(1, $p->psuEmpty);
    }

    public function test_standard_cpu_memory(): void
    {
        $p = self::$standard;

        $this->assertSame(1.0, $p->cpuSystem1h);
        $this->assertSame(1048576, $p->memoryBySlot['System']['total_kb']);
        $this->assertSame(721104, $p->memoryBySlot['System']['free_kb']);
    }

    public function test_standard_ports(): void
    {
        $p = self::$standard;

        // 52 puertos en show ports information; nomenclatura sin ":" (standalone)
        $this->assertCount(52, $p->portLinkTransitions);
        // Contador Link UPS pegado al guion previo: "- / -1955"
        $this->assertSame(1955, $p->portLinkTransitions['29']);
        $this->assertSame('active', $p->portStates['29']);

        // rxerrors con "| exclude": solo filas distintas de cero (10)
        $this->assertCount(10, $p->rxErrors);
        // Puerto 32: capa física dañada (CRC + fragmentos + align masivos)
        $this->assertSame(39415678, $p->rxErrors['32']['crc']);
        $this->assertSame(59276416, $p->rxErrors['32']['frag']);
        $this->assertSame(118209053, $p->rxErrors['32']['align']);
        // Puerto ausente = contadores en cero, no dato faltante
        $this->assertArrayNotHasKey('1', $p->rxErrors);

        // Congestión
        $this->assertCount(19, $p->congestion);
        $this->assertSame(66597758, $p->congestion['30']['drops']);
        $this->assertSame(0, $p->congestion['30']['last_second']);

        // Óptica del puerto 47
        $this->assertSame(-7.01, $p->transceivers['47']['rx_dbm']);

        // Puertos que negociaron a 10 Mbps (log)
        $this->assertContains('29', $p->portsAt10Mbps);
        $this->assertCount(5, $p->portsAt10Mbps);
    }

    public function test_standard_logs_and_management(): void
    {
        $p = self::$standard;

        $this->assertSame([], $p->unexpectedReboots);
        $this->assertSame(0, $p->authFailures);
        $this->assertSame([], $p->coreDumps);

        // 200 eventos Erro de SNMP deshabilitado, agrupados por mensaje único
        // (el original + variantes "Previous message repeated ...")
        $snmp = array_values(array_filter($p->logErrors, fn ($e) => str_contains($e['component'], 'ReqDropSNMPDsbl')));
        $this->assertNotEmpty($snmp);
        $this->assertSame(200, array_sum(array_column($snmp, 'count')));
        $this->assertSame('Erro', $snmp[0]['severity']);

        $this->assertStringStartsWith('Disabled', (string) $p->sshStatus);
        $this->assertFalse($p->snmpDisabled);
        $this->assertFalse($p->ntpConfigured);
        $this->assertSame(93, $p->fdbTotal);
        $this->assertSame(1918, $p->odometerDays['Switch']);

        // PoE standalone
        $this->assertCount(1, $p->poe);
        $this->assertSame(740.0, $p->poe[0]['budgeted_w']);
    }

    // ------------------------------------------------------------------
    // Stack X460, EXOS 12.5 (delimitador "-> comando" y bloque "!  show fans")
    // ------------------------------------------------------------------

    public function test_stack_identity(): void
    {
        $p = self::$stack;

        $this->assertSame('Stack', $p->sysName);
        $this->assertSame('02:04:96:7E:1E:E5', $p->systemMac);
        $this->assertTrue($p->isStack);
        $this->assertSame(137, $p->bootCount);
        $this->assertSame('2026-07-03 02:47:05', $p->capturedAt?->format('Y-m-d H:i:s'));
        $this->assertSame('12.5.4.5', $p->exosVersion);
        $this->assertSame(2011, $p->firmwareBuildYear);

        // Seriales por slot en stack
        $this->assertSame([
            'Slot-1' => '1233G-80233',
            'Slot-2' => '1233G-80239',
            'Slot-3' => '1233G-80240',
            'Slot-4' => '1233G-80234',
        ], $p->serialNumbers);
    }

    public function test_stack_environment_multislot(): void
    {
        $p = self::$stack;

        // 4 nodos con temperatura; formato por slot de EXOS 12.x
        $this->assertCount(4, $p->temperatures);
        $this->assertSame(26.5, $p->temperatures[3]['temp']);
        $this->assertSame(55.0, $p->temperatures[0]['max']);
        $this->assertSame(40.0, $p->temperatures[0]['normal_max']); // rango 0-40 del X460

        // Bloque "!  show fans" agregado manualmente (tercer delimitador)
        $this->assertSame(16, $p->fansOk);
        $this->assertCount(8, $p->fanTrays);

        // Detalle por ventilador con RPM (show fans / show fans detail)
        $this->assertCount(4, $p->fanDetails['Fan Tray-1 FanTray-1']['fans']);
        $this->assertSame(7021, $p->fanDetails['Fan Tray-1 FanTray-1']['fans'][0]['rpm']);
        $this->assertSame([], $p->fanDetails['FanTray-5']['fans']); // tray vacío
        $this->assertSame('Empty', $p->fanTrays['FanTray-5']);
        $this->assertSame('Operational', $p->fanTrays['Fan Tray-1 FanTray-1']);

        // PSU en formato matriz P/F de 12.x
        $this->assertSame(4, $p->psuOn);
        $this->assertSame(0, $p->psuFailed);

        // Memoria por slot
        $this->assertSame(843944, $p->memoryBySlot['Slot-1']['free_kb']);

        // Odómetros (edad del hardware)
        $this->assertSame(4707, $p->odometerDays['Slot-1']);
        $this->assertSame(4698, $p->odometerDays['Slot-4']);
    }

    public function test_stack_ports_and_logs(): void
    {
        $p = self::$stack;

        // rxerror singular de 12.x, sin filtro exclude: todas las filas presentes
        $this->assertCount(216, $p->rxErrors);
        $this->assertSame(0, $p->rxErrors['1:1']['crc']);

        // authFail y puertos a 10 Mbps detectados en el log
        $this->assertSame(2, $p->authFailures);
        $this->assertCount(14, $p->portsAt10Mbps);
        $this->assertContains('1:45', $p->portsAt10Mbps);

        // Configuración: snmp deshabilitado y ópticas con velocidad forzada
        $this->assertTrue($p->snmpDisabled);
        $this->assertFalse($p->ntpConfigured);
        $this->assertCount(8, $p->forcedSpeedPorts);
        $this->assertSame(10000, $p->forcedSpeedPorts['1:53']);
    }

    public function test_stack_tolerates_missing_sections(): void
    {
        $p = self::$stack;

        // EXOS 12.x no trae cpu-monitoring ni ports information: el parser
        // continúa y lo registra como advertencia (parser tolerante a fallos).
        $this->assertNull($p->cpuSystem1h);
        $this->assertSame([], $p->portLinkTransitions);
        $this->assertNotEmpty($p->warnings);

        $warningsText = implode(' | ', $p->warnings);
        $this->assertStringContainsString('cpu-monitoring', $warningsText);
        $this->assertStringContainsString('ports information', $warningsText);
    }

    // ------------------------------------------------------------------
    // Robustez
    // ------------------------------------------------------------------

    public function test_empty_or_invalid_file_does_not_crash(): void
    {
        $parser = new ExosParser;

        $empty = $parser->parse('');
        $this->assertNotEmpty($empty->warnings);
        $this->assertNull($empty->sysName);

        $garbage = $parser->parse("esto no es un tech support\nlinea 2\n");
        $this->assertNotEmpty($garbage->warnings);
    }

    public function test_partial_file_parses_available_sections(): void
    {
        $parser = new ExosParser;

        $partial = <<<'TXT'
        ->show switch
        SysName:          TEST-SW
        System Type:      X440G2-24t
        System MAC:       00:11:22:33:44:55
        System UpTime:    1 day 2 hours 3 minutes 4 seconds
        Boot Count:       5
        Current Time:     Mon Jan  5 10:00:00 2026
        TXT;

        $result = $parser->parse($partial);

        $this->assertSame('TEST-SW', $result->sysName);
        $this->assertSame(93784, $result->uptimeSeconds);
        $this->assertSame('2026-01-05 10:00:00', $result->capturedAt?->format('Y-m-d H:i:s'));
        // Las demás secciones faltan pero no abortan el parseo
        $this->assertNotEmpty($result->warnings);
    }
}
