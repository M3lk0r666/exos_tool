<?php

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reports.view');
    }

    public function view(User $user, Report $report): bool
    {
        return $user->can('reports.view');
    }

    public function update(User $user, Report $report): bool
    {
        return $user->can('reports.edit') && $report->status === ReportStatus::Draft;
    }

    public function issue(User $user, Report $report): bool
    {
        return $user->can('reports.issue') && $report->status === ReportStatus::Draft;
    }
}
