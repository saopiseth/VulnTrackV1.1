<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VulnRemediation;
use App\Models\VulnTracked;

class VulnTrackedPolicy
{
    /** Admins and Assessors may list all findings; others may list (filtered by scope). */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Guards against IDOR on any future per-finding route.
     * Admins and Assessors pass unconditionally.
     * Patch Administrators pass only when the finding's remediation is
     * assigned to one of their groups.
     */
    public function view(User $user, VulnTracked $finding): bool
    {
        if ($user->isAdministrator() || $user->isAssessor()) {
            return true;
        }

        $user->loadMissing('groups');
        $groupIds = $user->groups->pluck('id');

        if ($groupIds->isEmpty()) {
            return false;
        }

        return VulnRemediation::where('assessment_id', $finding->assessment_id)
            ->where('plugin_id',  $finding->plugin_id)
            ->where('ip_address', $finding->ip_address)
            ->whereIn('assigned_group_id', $groupIds)
            ->exists();
    }
}
