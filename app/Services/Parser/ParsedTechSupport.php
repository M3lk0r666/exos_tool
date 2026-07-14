<?php

namespace App\Services\Parser;

use DateTimeImmutable;

/**
 * DTO con el resultado normalizado del parseo de un tech-support EXOS.
 * Todos los campos son opcionales: el parser es tolerante a fallos y
 * registra en $warnings las secciones que no pudo interpretar.
 */
class ParsedTechSupport
{
    // Identificación (show switch)
    public ?string $sysName = null;
    public ?string $systemType = null;
    public ?string $systemMac = null;
    public ?string $uptimeText = null;
    public ?int $uptimeSeconds = null;
    public ?string $bootTime = null;
    public ?int $bootCount = null;
    public ?DateTimeImmutable $capturedAt = null;
    public ?string $imageBooted = null;
    public ?string $configName = null;
    public bool $isStack = false;

    // Firmware (show version)
    public ?string $exosVersion = null;
    public ?string $firmwareBuildDate = null;
    public ?int $firmwareBuildYear = null;
    public ?string $bootRom = null;

    /**
     * Números de serie por unidad (show version): "Switch" en standalone,
     * "Slot-N" en stack. Segundo bloque después de los dos puntos.
     *
     * @var array<string, string>
     */
    public array $serialNumbers = [];

    /** @var array<string, string> Números de parte por unidad (primer bloque) */
    public array $partNumbers = [];

    /** @var array<int, array{slot: string, type: string, state: string, ports: int}> */
    public array $slots = [];

    /** @var array<string, int> Días de servicio por slot (show odometers) */
    public array $odometerDays = [];

    /** @var array<int, array{unit: string, model: string, temp: float, status: string, min: float, max: float}> */
    public array $temperatures = [];

    // Ventiladores
    public int $fansOk = 0;
    /** @var array<int, string> Descripciones de ventiladores en falla */
    public array $fansFailed = [];
    /** @var array<string, string> Estado por FanTray (Operational/Empty/Failed...) */
    public array $fanTrays = [];

    /** @var array<string, array{state: ?string, fans: array<int, array{fan: string, state: string, rpm: ?int}>}> */
    public array $fanDetails = [];

    // Alimentación
    public int $psuOn = 0;
    public int $psuFailed = 0;
    public int $psuEmpty = 0;
    public ?float $powerUsageWatts = null;

    // CPU (show cpu-monitoring, columna 1 hora)
    public ?float $cpuSystem1h = null;
    /** @var array<int, array{process: string, util_1h: float}> */
    public array $cpuHighProcesses = [];

    /** @var array<string, array{total_kb: int, free_kb: int}> Memoria por slot ("System" si standalone) */
    public array $memoryBySlot = [];

    /** @var array<string, int> Transiciones de link acumuladas por puerto */
    public array $portLinkTransitions = [];
    /** @var array<string, string> Estado de link por puerto (active/ready/...) */
    public array $portStates = [];

    /** @var array<string, array{crc: int, over: int, under: int, frag: int, jabber: int, align: int, lost: int}> */
    public array $rxErrors = [];

    /** @var array<int, string> Puertos de stack con errores rx */
    public array $stackPortsWithErrors = [];

    /** @var array<string, array{drops: int, last_second: int}> Congestión por puerto */
    public array $congestion = [];

    /** @var array<string, array{temp: ?float, tx_dbm: ?float, rx_dbm: ?float}> Ópticas por puerto */
    public array $transceivers = [];

    // Logs
    public int $logTotal = 0;
    /** @var array<int, string> Fechas (m/d/Y) con reinicios inesperados */
    public array $unexpectedReboots = [];
    /** @var array<int, array{date: string, severity: string, component: string, message: string, count: int}> */
    public array $logErrors = [];
    /** @var array<int, string> Mensajes de conflicto de óptica */
    public array $opticConflicts = [];
    public int $authFailures = 0;
    /** @var array<string, int> Eventos link-down por puerto (flapping observado en log) */
    public array $linkDownEvents = [];
    /** @var array<int, string> Puertos que negociaron a 10 Mbps */
    public array $portsAt10Mbps = [];

    // Stacking
    public bool $stackRingComplete = false;
    /** @var array<int, array{mac: string, slot: string, state: string, role: string}> */
    public array $stackNodes = [];

    /** @var array<int, string> Slots con core dumps presentes */
    public array $coreDumps = [];

    public ?int $fdbTotal = null;

    // Gestión y seguridad
    public ?string $sshStatus = null;
    public ?string $telnetStatus = null;
    public bool $snmpDisabled = false;
    public bool $ntpConfigured = false;
    /** @var array<string, int> Puertos con velocidad forzada (configure ports X auto off speed N) */
    public array $forcedSpeedPorts = [];

    public ?string $licenseLevel = null;

    /** @var array<int, array{slot: string, status: string, budgeted_w: ?float, measured_w: ?float}> */
    public array $poe = [];

    /** @var array<int, string> Advertencias de secciones no interpretadas */
    public array $warnings = [];

    public function addWarning(string $message): void
    {
        $this->warnings[] = $message;
    }

    /** Resumen compacto para captures.raw_summary. */
    public function toRawSummary(): array
    {
        return [
            'sysname' => $this->sysName,
            'system_type' => $this->systemType,
            'system_mac' => $this->systemMac,
            'is_stack' => $this->isStack,
            'exos_version' => $this->exosVersion,
            'firmware_build_date' => $this->firmwareBuildDate,
            'bootrom' => $this->bootRom,
            'serial_numbers' => $this->serialNumbers,
            'part_numbers' => $this->partNumbers,
            'uptime_text' => $this->uptimeText,
            'boot_count' => $this->bootCount,
            'captured_at' => $this->capturedAt?->format('Y-m-d H:i:s'),
            'license' => $this->licenseLevel,
            'slots' => $this->slots,
            'odometer_days' => $this->odometerDays,
            'temperatures' => $this->temperatures,
            'fans' => ['ok' => $this->fansOk, 'failed' => $this->fansFailed, 'trays' => $this->fanTrays, 'detail' => $this->fanDetails],
            'power' => ['on' => $this->psuOn, 'failed' => $this->psuFailed, 'empty' => $this->psuEmpty, 'usage_w' => $this->powerUsageWatts],
            'cpu_1h' => $this->cpuSystem1h,
            'cpu_high_processes' => $this->cpuHighProcesses,
            'memory' => $this->memoryBySlot,
            'ports' => [
                'states' => count($this->portStates),
                'active' => count(array_filter($this->portStates, fn ($s) => $s === 'active')),
                'with_rx_errors' => count($this->rxErrors),
                'with_congestion' => count($this->congestion),
                'at_10mbps' => $this->portsAt10Mbps,
            ],
            'transceivers' => $this->transceivers,
            'logs' => [
                'total' => $this->logTotal,
                'unexpected_reboots' => $this->unexpectedReboots,
                'errors' => count($this->logErrors),
                'auth_failures' => $this->authFailures,
                'optic_conflicts' => count($this->opticConflicts),
            ],
            'stacking' => [
                'ring' => $this->stackRingComplete,
                'nodes' => $this->stackNodes,
                'ports_with_errors' => $this->stackPortsWithErrors,
            ],
            'core_dumps' => $this->coreDumps,
            'fdb_total' => $this->fdbTotal,
            'management' => [
                'ssh' => $this->sshStatus,
                'telnet' => $this->telnetStatus,
                'snmp_disabled' => $this->snmpDisabled,
                'ntp_configured' => $this->ntpConfigured,
                'forced_speed_ports' => $this->forcedSpeedPorts,
            ],
            'poe' => $this->poe,
            'warnings' => $this->warnings,
        ];
    }
}
