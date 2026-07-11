<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Device;
use App\Services\Parser\ParsedTechSupport;

/**
 * Crea o actualiza el equipo a partir de un tech-support parseado.
 * Identificación: System MAC + SysName (sección 5.2 del prompt maestro).
 */
class DeviceResolver
{
    public function resolve(Client $client, ParsedTechSupport $parsed): ?Device
    {
        if (! $parsed->systemMac) {
            return null;
        }

        $device = Device::withTrashed()->firstOrNew(['system_mac' => $parsed->systemMac]);

        if ($device->trashed()) {
            $device->restore();
        }

        $device->client_id = $client->id;
        $device->sysname = $parsed->sysName ?: ($device->sysname ?: 'desconocido');
        // Serial de la unidad principal (Switch en standalone, Slot-1 en stack).
        $device->serial_number = $parsed->serialNumbers['Switch']
            ?? $parsed->serialNumbers['Slot-1']
            ?? (reset($parsed->serialNumbers) ?: $device->serial_number);
        $device->model = $parsed->systemType
            ? trim(str_replace('(Stack)', '', $parsed->systemType))
            : $device->model;
        $device->is_stack = $parsed->isStack || $device->is_stack;
        $device->save();

        return $device;
    }
}
