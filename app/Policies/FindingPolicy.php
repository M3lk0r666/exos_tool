<?php

namespace App\Policies;

use App\Models\Finding;
use App\Models\User;

class FindingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('findings.view');
    }

    public function view(User $user, Finding $finding): bool
    {
        return $user->can('findings.view');
    }

    public function create(User $user): bool
    {
        return $user->can('findings.edit');
    }

    public function update(User $user, Finding $finding): bool
    {
        return $user->can('findings.edit');
    }

    public function delete(User $user, Finding $finding): bool
    {
        return $user->can('findings.edit');
    }
}
