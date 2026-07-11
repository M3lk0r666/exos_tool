<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;

class DevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('devices.view');
    }

    public function view(User $user, Device $device): bool
    {
        return $user->can('devices.view');
    }

    public function update(User $user, Device $device): bool
    {
        return $user->can('devices.manage');
    }
}
