<?php

namespace App\Policies;

use App\Models\Capture;
use App\Models\User;

class CapturePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('captures.view');
    }

    public function view(User $user, Capture $capture): bool
    {
        return $user->can('captures.view');
    }

    public function create(User $user): bool
    {
        return $user->can('captures.upload');
    }

    public function delete(User $user, Capture $capture): bool
    {
        return $user->can('captures.upload');
    }
}
