<?php

namespace App\Services;

use App\Models\Capture;
use App\Models\Metric;
use App\Services\Parser\ParsedTechSupport;

/**
 * Convierte el resultado del parser en filas normalizadas de `metrics`
 * para habilitar comparativos y gráficos de tendencia (sección 5.4).
 */
class CaptureMetricsRecorder
{
    public function record(Capture $capture, ParsedTechSupport $p): int
    {
        $rows = [];
        $push = function (string $category, string $entity, string $metric, ?float $value, ?array $extra = null) use (&$rows, $capture) {
            $rows[] = [
                'capture_id' => $capture->id,
                'category' => $category,
                'entity' => $entity,
                'metric' => $metric,
                'value' => $value,
                'extra' => $extra ? json_encode($extra) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        };

        // Sistema
        if ($p->uptimeSeconds !== null) {
            $push('system', 'System', 'uptime_seconds', $p->uptimeSeconds);
        }
        if ($p->bootCount !== null) {
            $push('system', 'System', 'boot_count', $p->bootCount);
        }
        if ($p->firmwareBuildYear !== null) {
            $push('system', 'System', 'firmware_build_year', $p->firmwareBuildYear);
        }
        if ($p->fdbTotal !== null) {
            $push('system', 'System', 'fdb_total', $p->fdbTotal);
        }

        // Ambiente
        foreach ($p->temperatures as $t) {
            $push('env', $t['unit'], 'temperature', $t['temp'], [
                'status' => $t['status'], 'min' => $t['min'], 'max' => $t['max'],
            ]);
        }
        $push('env', 'System', 'fans_ok', $p->fansOk);
        $push('env', 'System', 'fans_failed', count($p->fansFailed), $p->fansFailed ?: null);

        // Alimentación
        $push('power', 'System', 'psu_on', $p->psuOn);
        $push('power', 'System', 'psu_failed', $p->psuFailed);
        if ($p->powerUsageWatts !== null) {
            $push('power', 'System', 'usage_watts', $p->powerUsageWatts);
        }

        // CPU / memoria
        if ($p->cpuSystem1h !== null) {
            $push('cpu', 'System', 'util_1h_pct', $p->cpuSystem1h);
        }
        foreach ($p->memoryBySlot as $slot => $mem) {
            $push('memory', $slot, 'total_kb', $mem['total_kb']);
            $push('memory', $slot, 'free_kb', $mem['free_kb']);
            if ($mem['total_kb'] > 0) {
                $push('memory', $slot, 'free_pct', round($mem['free_kb'] / $mem['total_kb'] * 100, 2));
            }
        }

        // Puertos
        foreach ($p->portLinkTransitions as $port => $ups) {
            $push('ports', (string) $port, 'link_transitions', $ups, [
                'state' => $p->portStates[$port] ?? null,
            ]);
        }
        foreach ($p->rxErrors as $port => $err) {
            foreach (['crc' => 'crc_errors', 'over' => 'oversize', 'under' => 'undersize',
                'frag' => 'fragments', 'jabber' => 'jabber', 'align' => 'align_errors',
                'lost' => 'lost'] as $key => $metric) {
                if ($err[$key] > 0) {
                    $push('ports', (string) $port, $metric, $err[$key]);
                }
            }
        }
        foreach ($p->congestion as $port => $c) {
            $push('ports', (string) $port, 'congestion_drops', $c['drops']);
            if ($c['last_second'] > 0) {
                $push('ports', (string) $port, 'congestion_last_second', $c['last_second']);
            }
        }
        foreach ($p->linkDownEvents as $port => $count) {
            $push('ports', (string) $port, 'link_down_events', $count);
        }

        // Ópticas
        foreach ($p->transceivers as $port => $t) {
            if ($t['temp'] !== null) {
                $push('optics', (string) $port, 'temperature', $t['temp']);
            }
            if ($t['tx_dbm'] !== null) {
                $push('optics', (string) $port, 'tx_dbm', $t['tx_dbm']);
            }
            if ($t['rx_dbm'] !== null) {
                $push('optics', (string) $port, 'rx_dbm', $t['rx_dbm']);
            }
        }

        // Logs
        $push('logs', 'System', 'total_events', $p->logTotal);
        $push('logs', 'System', 'unexpected_reboots', count($p->unexpectedReboots), $p->unexpectedReboots ?: null);
        $push('logs', 'System', 'error_events', array_sum(array_column($p->logErrors, 'count')));
        $push('logs', 'System', 'auth_failures', $p->authFailures);

        // Stacking
        if ($p->isStack) {
            $push('stack', 'System', 'ring_complete', $p->stackRingComplete ? 1 : 0);
            $push('stack', 'System', 'nodes', count($p->stackNodes));
            $push('stack', 'System', 'ports_with_errors', count($p->stackPortsWithErrors), $p->stackPortsWithErrors ?: null);
        }

        // PoE
        foreach ($p->poe as $poe) {
            $push('poe', $poe['slot'], 'budgeted_watts', $poe['budgeted_w']);
            $push('poe', $poe['slot'], 'measured_watts', $poe['measured_w']);
        }

        // Odómetros
        foreach ($p->odometerDays as $slot => $days) {
            $push('hardware', $slot, 'service_days', $days);
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Metric::insert($chunk);
        }

        return count($rows);
    }
}
