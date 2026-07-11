<?php

namespace App\Services\Parser;

use DateTimeImmutable;
use Throwable;

/**
 * Parser estructurado de archivos "show tech-support all" de EXOS (Anexo A).
 *
 * Tolerante a fallos: cada sección se extrae de forma independiente; si una
 * falta o cambia de formato se registra una advertencia y se continúa.
 * Cubre las variantes de formato de EXOS 12.x, 16.x y 22.x validadas contra
 * los archivos reales de ejemplos-tech/.
 */
class ExosParser
{
    /**
     * Alias de comandos por versión de EXOS. La búsqueda es por prefijo e
     * ignora los sufijos de pipe ("| exclude ...").
     */
    private const ALIASES = [
        'switch' => ['show switch'],
        'version' => ['show version detail', 'show version'],
        'slot' => ['show slot detail', 'show slot'],
        'odometers' => ['show odometers'],
        'temperature' => ['show temperature'],
        'fans' => ['show fans detail', 'show fans'],
        'power' => ['show power detail', 'show power'],
        'cpu' => ['show cpu-monitoring'],
        'memory' => ['show memory'],
        'ports_info' => ['show ports information'],
        'rxerrors' => ['show ports rxerrors', 'show port rxerrors', 'show port rxerror'],
        'stack_rxerrors' => ['show port stack-ports rxerrors', 'show ports stack-ports rxerrors'],
        'congestion' => ['Port Congestion', 'show ports congestion'],
        'transceiver' => ['show ports transceiver info', 'show port transceiver information', 'show port transceiver info'],
        'log' => ['show log messages nvram', 'show log'],
        'stacking' => ['show stacking'],
        'fdb' => ['show fdb stats'],
        'management' => ['show management'],
        'config' => ['show configuration', 'show config'],
        'license' => ['show license', 'show licenses'],
        'inline_power' => ['show inline-power'],
    ];

    public function __construct(
        private readonly SectionSplitter $splitter = new SectionSplitter,
    ) {}

    /** @var array<string, string> */
    private array $sections = [];

    public function parse(string $text): ParsedTechSupport
    {
        $this->sections = $this->splitter->split($text);
        $result = new ParsedTechSupport;

        if ($this->sections === []) {
            $result->addWarning('No se encontraron secciones "->comando"; ¿es un tech-support válido?');

            return $result;
        }

        $steps = [
            'show switch' => fn () => $this->extractSwitch($result),
            'show version' => fn () => $this->extractVersion($result),
            'show slot' => fn () => $this->extractSlots($result),
            'show odometers' => fn () => $this->extractOdometers($result),
            'show temperature' => fn () => $this->extractTemperatures($result),
            'show fans' => fn () => $this->extractFans($result),
            'show power' => fn () => $this->extractPower($result),
            'show cpu-monitoring' => fn () => $this->extractCpu($result),
            'show memory' => fn () => $this->extractMemory($result),
            'show ports information' => fn () => $this->extractPortsInformation($result),
            'show ports rxerrors' => fn () => $this->extractRxErrors($result),
            'stack-ports rxerrors' => fn () => $this->extractStackRxErrors($result),
            'Port Congestion' => fn () => $this->extractCongestion($result),
            'transceiver' => fn () => $this->extractTransceivers($result),
            'show log' => fn () => $this->extractLogs($result),
            'show stacking' => fn () => $this->extractStacking($result),
            'system-dump' => fn () => $this->extractCoreDumps($result),
            'show fdb stats' => fn () => $this->extractFdb($result),
            'show management' => fn () => $this->extractManagement($result),
            'show license' => fn () => $this->extractLicense($result),
            'show inline-power' => fn () => $this->extractPoe($result),
        ];

        foreach ($steps as $label => $step) {
            try {
                $step();
            } catch (Throwable $e) {
                $result->addWarning("Sección [{$label}]: {$e->getMessage()}");
            }
        }

        return $result;
    }

    // ------------------------------------------------------------------
    // Búsqueda de secciones
    // ------------------------------------------------------------------

    private function section(string $aliasKey): string
    {
        foreach (self::ALIASES[$aliasKey] as $prefix) {
            foreach ($this->sections as $command => $content) {
                if (str_starts_with($command, $prefix)) {
                    return $content;
                }
            }
        }

        return '';
    }

    private static function kv(string $text, string $key): ?string
    {
        if (preg_match('/^'.preg_quote($key, '/').'\s*:\s*(.+)$/m', $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Extractores por sección
    // ------------------------------------------------------------------

    private function extractSwitch(ParsedTechSupport $r): void
    {
        $sw = $this->section('switch');
        if ($sw === '') {
            $r->addWarning('Sección "show switch" no encontrada.');

            return;
        }

        $r->sysName = self::kv($sw, 'SysName');
        $r->systemType = self::kv($sw, 'System Type');
        $r->systemMac = self::kv($sw, 'System MAC');
        $r->uptimeText = self::kv($sw, 'System UpTime');
        $r->bootTime = self::kv($sw, 'Boot Time');
        $r->imageBooted = self::kv($sw, 'Image Booted');
        $r->configName = self::kv($sw, 'Config Booted') ?? self::kv($sw, 'Config Selected');
        $r->isStack = $r->systemType !== null && str_contains($r->systemType, '(Stack)');

        $bootCount = self::kv($sw, 'Boot Count');
        $r->bootCount = $bootCount !== null ? (int) $bootCount : null;

        if ($r->uptimeText !== null) {
            $r->uptimeSeconds = self::uptimeToSeconds($r->uptimeText);
        }

        // Fecha de captura: "Current Time" del propio archivo (Anexo A).
        $current = self::kv($sw, 'Current Time');
        if ($current !== null) {
            $normalized = preg_replace('/\s+/', ' ', $current);
            $dt = DateTimeImmutable::createFromFormat('D M j H:i:s Y', $normalized);
            if ($dt !== false) {
                $r->capturedAt = $dt;
            } else {
                $r->addWarning("No se pudo interpretar Current Time: {$current}");
            }
        } else {
            $r->addWarning('Current Time no encontrado; se usará la fecha de subida como referencia.');
        }
    }

    private function extractVersion(ParsedTechSupport $r): void
    {
        $ver = $this->section('version');
        $sw = $this->section('switch');

        // Versión: primero de "Image : ExtremeXOS version X", si no de Primary ver.
        if (preg_match('/Image\s*:\s*ExtremeXOS version (\S+)/', $ver, $m)) {
            $r->exosVersion = $m[1];
        } elseif (($primary = self::kv($sw, 'Primary ver')) !== null) {
            $r->exosVersion = preg_split('/\s+/', $primary)[0] ?: null;
        } elseif (preg_match('/IMG:\s*([\d.]+)/', $ver, $m)) {
            $r->exosVersion = $m[1];
        }

        // Fecha de compilación de la imagen (para FW-AGE).
        if (preg_match('/ExtremeXOS version \S+.*?on\s+(\w+\s+\w+\s+\d+\s+[\d:]+\s+\S+\s+(\d{4}))/s', $ver, $m)) {
            $r->firmwareBuildDate = $m[1];
            $r->firmwareBuildYear = (int) $m[2];
        }

        if (preg_match('/^BootROM\s*:\s*(\S+)/m', $ver, $m)) {
            $r->bootRom = $m[1];
        } elseif (preg_match('/BootROM:\s*(\S+)/', $ver, $m)) {
            $r->bootRom = $m[1];
        }

        // Números de serie por unidad:
        //   Switch  : 800618-00-18 1910N-44546 Rev 18 BootROM: ...  (standalone)
        //   Slot-1  : 800324-00-09 1233G-80233 Rev 9.0 BootROM: ... (stack)
        // Primer bloque = número de parte; segundo = número de serie.
        if (preg_match_all('/^(Switch|Slot-\d+)\s*:\s*(\S+)\s+(\S+)\s+Rev/m', $ver, $ms, PREG_SET_ORDER)) {
            foreach ($ms as $m) {
                $r->partNumbers[$m[1]] = $m[2];
                $r->serialNumbers[$m[1]] = $m[3];
            }
        }

        if ($r->serialNumbers === []) {
            $r->addWarning('No se pudieron extraer números de serie de "show version".');
        }
    }

    private function extractSlots(ParsedTechSupport $r): void
    {
        $slot = $this->section('slot');

        if (preg_match_all('/^(Slot-\d+)\s+(\S+)\s+\S+\s+(Operational|Empty|Failed)\s+(\d+)\s*$/m', $slot, $ms, PREG_SET_ORDER)) {
            foreach ($ms as $m) {
                $r->slots[] = ['slot' => $m[1], 'type' => $m[2], 'state' => $m[3], 'ports' => (int) $m[4]];
            }
        }
    }

    private function extractOdometers(ParsedTechSupport $r): void
    {
        $odo = $this->section('odometers');

        if (preg_match_all('/^\s*(Slot-\d+|Switch)\s*:\s*\S+\s+(\d+)\s/m', $odo, $ms, PREG_SET_ORDER)) {
            foreach ($ms as $m) {
                $r->odometerDays[$m[1]] = (int) $m[2];
            }
        }
    }

    private function extractTemperatures(ParsedTechSupport $r): void
    {
        $temp = $this->section('temperature');
        if ($temp === '') {
            $r->addWarning('Sección "show temperature" no encontrada.');

            return;
        }

        // Formatos: "Switch : X440G2-48p-10G4 61.50 Normal 0 10-100 110"
        //           "Slot-1 : X460-48p 25.00 Normal -10 0-40 55"
        if (preg_match_all('/^\s*(\S+)\s*:\s*(\S+)\s+([\d.]+)\s+(\w+)\s+(-?\d+)\s+\S+\s+(\d+)\s*$/m', $temp, $ms, PREG_SET_ORDER)) {
            foreach ($ms as $m) {
                $r->temperatures[] = [
                    'unit' => $m[1],
                    'model' => $m[2],
                    'temp' => (float) $m[3],
                    'status' => $m[4],
                    'min' => (float) $m[5],
                    'max' => (float) $m[6],
                ];
            }
        }
    }

    private function extractFans(ParsedTechSupport $r): void
    {
        $fans = $this->section('fans');
        if ($fans === '') {
            $r->addWarning('Sección "show fans" no encontrada.');

            return;
        }

        $r->fansOk = preg_match_all('/Fan-\d+:\s+Operational at \d+ RPM/', $fans);

        if (preg_match_all('/(Fan-\d+):\s+(?!Operational)(\S+)/', $fans, $ms, PREG_SET_ORDER)) {
            foreach ($ms as $m) {
                $r->fansFailed[] = "{$m[1]} {$m[2]}";
            }
        }

        // Estado por FanTray. Variantes: "Slot-1 FanTray information:" (22.x),
        // "Fan Tray-1 FanTray-1 information:" y "FanTray-2 information:" (12.x).
        if (preg_match_all('/^(.*?)(FanTray(?:-\d+)?) information:\s*\n\s*State:\s+(\S+)/m', $fans, $ms, PREG_SET_ORDER)) {
            foreach ($ms as $m) {
                $label = trim($m[1].$m[2]);
                $r->fanTrays[$label] = $m[3];
            }
        }
    }

    private function extractPower(ParsedTechSupport $r): void
    {
        $pw = $this->section('power');
        if ($pw === '') {
            $r->addWarning('Sección "show power" no encontrada.');

            return;
        }

        // Formato 16.x/22.x: bloques "PowerSupply N information: State : Powered On"
        $r->psuOn = preg_match_all('/State\s*:\s*Powered On/', $pw);
        $r->psuFailed = preg_match_all('/State\s*:\s*(Failed|Powered Off)/', $pw);
        $r->psuEmpty = preg_match_all('/State\s*:\s*Empty/', $pw);

        // Formato 12.x (stack): matriz por slot con flags P/F/-
        if ($r->psuOn === 0 && $r->psuFailed === 0 && $r->psuEmpty === 0) {
            if (preg_match_all('/^(Slot-\d+)\s+\S+\s+([PF\-\s]+)$/m', $pw, $ms, PREG_SET_ORDER)) {
                foreach ($ms as $m) {
                    $r->psuOn += substr_count($m[2], 'P');
                    $r->psuFailed += substr_count($m[2], 'F');
                }
            }
        }

        if (preg_match('/System Power Usage\s*:\s*([\d.]+)\s*W/', $pw, $m)) {
            $r->powerUsageWatts = (float) $m[1];
        }
    }

    private function extractCpu(ParsedTechSupport $r): void
    {
        $cpu = $this->section('cpu');
        if ($cpu === '') {
            // EXOS 12.x no incluye show cpu-monitoring en el tech-support.
            $r->addWarning('Sección "show cpu-monitoring" no encontrada (normal en EXOS 12.x).');

            return;
        }

        // Columnas: 5s 10s 30s 1m 5m 30m 1h Max — tomamos la de 1 hora (7a).
        if (preg_match_all('/^(\S+)\s+((?:[\d.]+\s+){6})([\d.]+)\s+[\d.]+\s/m', $cpu, $ms, PREG_SET_ORDER)) {
            foreach ($ms as $m) {
                $process = $m[1];
                $util1h = (float) $m[3];

                if ($process === 'System' && $r->cpuSystem1h === null) {
                    $r->cpuSystem1h = $util1h;
                } elseif ($process !== 'System' && $util1h >= 20.0) {
                    $r->cpuHighProcesses[] = ['process' => $process, 'util_1h' => $util1h];
                }
            }
        }

        if ($r->cpuSystem1h === null) {
            $r->addWarning('No se pudo extraer la utilización de CPU del sistema (fila System).');
        }
    }

    private function extractMemory(ParsedTechSupport $r): void
    {
        $mem = $this->section('memory');
        if ($mem === '') {
            $r->addWarning('Sección "show memory" no encontrada.');

            return;
        }

        // Con o sin prefijo de slot: " Slot-1    Total DRAM (KB): 1048576"
        preg_match_all('/^\s*(Slot-\d+)?\s*Total DRAM \(KB\):\s*(\d+)/m', $mem, $totals, PREG_SET_ORDER);
        preg_match_all('/^\s*(Slot-\d+)?\s*Free\s+\(KB\):\s*(\d+)/m', $mem, $frees, PREG_SET_ORDER);

        foreach ($totals as $i => $t) {
            $slot = $t[1] !== '' ? $t[1] : 'System';
            $r->memoryBySlot[$slot] = [
                'total_kb' => (int) $t[2],
                'free_kb' => isset($frees[$i]) ? (int) $frees[$i][2] : 0,
            ];
        }
    }

    private function extractPortsInformation(ParsedTechSupport $r): void
    {
        $info = $this->section('ports_info');
        if ($info === '') {
            // EXOS 12.x no incluye show ports information; el flapping se
            // estima con los eventos portLinkStateDown del log.
            $r->addWarning('Sección "show ports information" no encontrada (normal en EXOS 12.x).');

            return;
        }

        // "29  Em---x-  active  - / -1955  0 ..." — el contador Link UPS puede
        // venir pegado al guion previo (Anexo A).
        if (preg_match_all('/^(\d+(?::\d+)?)\s+\S+\s+(\w+)\s+-\s*\/\s*-\s*(\d+)\s/m', $info, $ms, PREG_SET_ORDER)) {
            foreach ($ms as $m) {
                $r->portStates[$m[1]] = $m[2];
                $r->portLinkTransitions[$m[1]] = (int) $m[3];
            }
        }
    }

    private function extractRxErrors(ParsedTechSupport $r): void
    {
        $rx = $this->section('rxerrors');
        if ($rx === '') {
            $r->addWarning('Sección "show ports rxerrors" no encontrada.');

            return;
        }

        // Nota Anexo A: en 22.x el comando viene con "| exclude" que oculta las
        // filas en cero — un puerto ausente tiene contadores en cero.
        if (preg_match_all('/^(\d+(?::\d+)?)\s+\w+\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s*$/m', $rx, $ms, PREG_SET_ORDER)) {
            foreach ($ms as $m) {
                $r->rxErrors[$m[1]] = [
                    'crc' => (int) $m[2],
                    'over' => (int) $m[3],
                    'under' => (int) $m[4],
                    'frag' => (int) $m[5],
                    'jabber' => (int) $m[6],
                    'align' => (int) $m[7],
                    'lost' => (int) $m[8],
                ];
            }
        }
    }

    private function extractStackRxErrors(ParsedTechSupport $r): void
    {
        $rx = $this->section('stack_rxerrors');

        if (preg_match_all('/^(\d+(?::\d+)?)\s+\w+\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s*$/m', $rx, $ms, PREG_SET_ORDER)) {
            foreach ($ms as $m) {
                $sum = array_sum(array_map('intval', array_slice($m, 2)));
                if ($sum > 0) {
                    $r->stackPortsWithErrors[] = $m[1];
                }
            }
        }
    }

    private function extractCongestion(ParsedTechSupport $r): void
    {
        $cong = $this->section('congestion');

        if (preg_match_all('/^(\d+(?::\d+)?)\s+(\d+)\s+(\d+)\s*$/m', $cong, $ms, PREG_SET_ORDER)) {
            foreach ($ms as $m) {
                $r->congestion[$m[1]] = ['drops' => (int) $m[2], 'last_second' => (int) $m[3]];
            }
        }
    }

    private function extractTransceivers(ParsedTechSupport $r): void
    {
        $tr = $this->section('transceiver');

        if (preg_match_all('/^(\d+(?::\d+)?)\s+([\d.]+|N\/A)\s+(-?[\d.]+|N\/A)\s+(-?[\d.]+|N\/A)/m', $tr, $ms, PREG_SET_ORDER)) {
            foreach ($ms as $m) {
                $r->transceivers[$m[1]] = [
                    'temp' => $m[2] === 'N/A' ? null : (float) $m[2],
                    'tx_dbm' => $m[3] === 'N/A' ? null : (float) $m[3],
                    'rx_dbm' => $m[4] === 'N/A' ? null : (float) $m[4],
                ];
            }
        }
    }

    private function extractLogs(ParsedTechSupport $r): void
    {
        // Une buffer + NVRAM (si existe).
        $logText = '';
        foreach ($this->sections as $command => $content) {
            if ($command === 'show log' || str_starts_with($command, 'show log messages nvram')) {
                $logText .= $content."\n";
            }
        }

        if ($logText === '') {
            $r->addWarning('Sección "show log" no encontrada.');

            return;
        }

        $r->logTotal = preg_match_all('/^\d{2}\/\d{2}\/\d{4}/m', $logText);

        // Formato: MM/DD/YYYY HH:MM:SS.ss <Sev:Componente> [Slot-x:] mensaje
        preg_match_all(
            '/^(\d{2}\/\d{2}\/\d{4})\s+[\d:.]+\s+<(\w+):([\w.]+)>\s+(?:(Slot-\d+):\s+)?(.*)$/m',
            $logText,
            $events,
            PREG_SET_ORDER
        );

        $reboots = [];
        $errors = [];
        $conflicts = [];

        foreach ($events as $e) {
            [, $date, $severity, $component, , $message] = $e;

            if (str_contains($component, 'UnexpctRebootDtect')) {
                $reboots[$date] = true;
            }

            if (in_array($severity, ['Erro', 'Crit'], true)) {
                $key = "{$severity}|{$component}|{$message}";
                if (! isset($errors[$key])) {
                    $errors[$key] = ['date' => $date, 'severity' => $severity, 'component' => $component, 'message' => $message, 'count' => 0];
                }
                $errors[$key]['count']++;
            }

            if (str_contains($component, 'OpticCfgCflct')) {
                $conflicts[$message] = true;
            }

            if (str_contains($component, 'authFail')) {
                $r->authFailures++;
            }

            if (str_contains($component, 'portLinkStateDown')
                && preg_match('/Port (\d+(?::\d+)?)/', $message, $pm)) {
                $r->linkDownEvents[$pm[1]] = ($r->linkDownEvents[$pm[1]] ?? 0) + 1;
            }

            if (str_contains($component, 'portLinkStateUp')
                && str_contains($message, '10 Mbps')
                && preg_match('/Port (\d+(?::\d+)?)/', $message, $pm)
                && ! in_array($pm[1], $r->portsAt10Mbps, true)) {
                $r->portsAt10Mbps[] = $pm[1];
            }
        }

        $r->unexpectedReboots = array_keys($reboots);
        usort($r->unexpectedReboots, fn ($a, $b) => strtotime(str_replace('/', '-', $a)) <=> strtotime(str_replace('/', '-', $b)));
        $r->logErrors = array_values($errors);
        $r->opticConflicts = array_keys($conflicts);
        sort($r->portsAt10Mbps, SORT_NATURAL);
    }

    private function extractStacking(ParsedTechSupport $r): void
    {
        $stk = $this->section('stacking');

        if ($stk !== '') {
            $r->stackRingComplete = str_contains($stk, 'Active Topology is a Ring');

            if (preg_match_all('/^\*?\s*([0-9a-fA-F:]{17})\s+(\d+)\s+(\S+)\s+(\S+)/m', $stk, $ms, PREG_SET_ORDER)) {
                foreach ($ms as $m) {
                    $r->stackNodes[] = ['mac' => $m[1], 'slot' => $m[2], 'state' => $m[3], 'role' => $m[4]];
                }
            }
        }

        // EXOS 12.x no trae "show stacking": el stack ya se detectó por
        // System Type "(Stack)" en extractSwitch.
        if ($r->isStack && $r->stackNodes === [] && $stk === '') {
            $r->addWarning('Equipo en stack sin sección "show stacking" (normal en EXOS 12.x); topología no verificable.');
        }
    }

    private function extractCoreDumps(ParsedTechSupport $r): void
    {
        foreach ($this->sections as $command => $content) {
            if (! str_starts_with($command, 'show debug system-dump')) {
                continue;
            }

            $clean = trim($content);
            if ($clean === ''
                || str_contains($clean, 'No core dump')
                || str_contains($clean, 'not present')) {
                continue;
            }

            // Conservador: solo marcar si hay evidencia real de dump.
            if (preg_match('/(core dump|dump saved|\.core|\.gz)/i', $clean)) {
                $r->coreDumps[] = $command;
            }
        }
    }

    private function extractFdb(ParsedTechSupport $r): void
    {
        $fdb = $this->section('fdb');

        if (preg_match('/Total:\s*(\d+)/', $fdb, $m)) {
            $r->fdbTotal = (int) $m[1];
        }
    }

    private function extractManagement(ParsedTechSupport $r): void
    {
        $mgmt = $this->section('management');
        $r->sshStatus = self::kv($mgmt, 'SSH access');
        $r->telnetStatus = self::kv($mgmt, 'Telnet access');

        $cfg = $this->section('config');
        $r->snmpDisabled = str_contains($cfg, 'disable snmp access');
        $r->ntpConfigured = (bool) preg_match('/^(configure|enable) s?ntp/m', $cfg);

        // Puertos con velocidad forzada (para OPT-CFG / PORT-10M).
        if (preg_match_all('/^configure ports? (\S+) auto off speed (\d+)/m', $cfg, $ms, PREG_SET_ORDER)) {
            foreach ($ms as $m) {
                $r->forcedSpeedPorts[$m[1]] = (int) $m[2];
            }
        }
    }

    private function extractLicense(ParsedTechSupport $r): void
    {
        $lic = $this->section('license');

        if (preg_match('/Enabled License Level:\s*\n?\s*(\S+)/', $lic, $m)) {
            $r->licenseLevel = $m[1];
        }
    }

    private function extractPoe(ParsedTechSupport $r): void
    {
        $poe = $this->section('inline_power');
        if ($poe === '') {
            return;
        }

        // Por slot (stack): "1  Enabled  Operational  740 W  120 W"
        if (preg_match_all('/^(\d+)\s+(Enabled|Disabled)\s+(\S+)\s+(\d+)\s*W\s+(\d+)\s*W/m', $poe, $ms, PREG_SET_ORDER)) {
            foreach ($ms as $m) {
                $r->poe[] = [
                    'slot' => $m[1],
                    'status' => $m[3],
                    'budgeted_w' => (float) $m[4],
                    'measured_w' => (float) $m[5],
                ];
            }
        } elseif (preg_match('/^(\S+)\s+(\d+)\s*W\s+(\d+)\s*W/m', $poe, $m)) {
            // Standalone: "Operational  740 W  0 W"
            $r->poe[] = [
                'slot' => 'Switch',
                'status' => $m[1],
                'budgeted_w' => (float) $m[2],
                'measured_w' => (float) $m[3],
            ];
        }
    }

    // ------------------------------------------------------------------

    private static function uptimeToSeconds(string $uptime): ?int
    {
        $seconds = 0;
        $found = false;

        foreach (['day' => 86400, 'hour' => 3600, 'minute' => 60, 'second' => 1] as $unit => $factor) {
            if (preg_match('/(\d+)\s+'.$unit.'/', $uptime, $m)) {
                $seconds += (int) $m[1] * $factor;
                $found = true;
            }
        }

        return $found ? $seconds : null;
    }
}
