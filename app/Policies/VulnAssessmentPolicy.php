<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VulnAssessment;

class VulnAssessmentPolicy
{
    /** Any authenticated user may list assessments. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Any authenticated user may view a single assessment. */
    public function view(User $user, VulnAssessment $assessment): bool
    {
        return true;
    }

    /** Any authenticated user except Patch Administrators may create an assessment. */
    public function create(User $user): bool
    {
        return !$user->isPatchAdministrator();
    }

    /** Only the owner or an administrator may edit/update an assessment. */
    public function update(User $user, VulnAssessment $assessment): bool
    {
        return $user->isAdministrator() || $assessment->created_by === $user->id;
    }

    /** Only the owner or an administrator may delete an assessment. */
    public function delete(User $user, VulnAssessment $assessment): bool
    {
        return $user->isAdministrator() || $assessment->created_by === $user->id;
    }

    /**
     * Only the owner or an administrator may upload scans, override OS,
     * reclassify findings, or assign remediations. Patch Administrators are view-only.
     */
    public function manage(User $user, VulnAssessment $assessment): bool
    {
        return !$user->isPatchAdministrator()
            && ($user->isAdministrator() || $assessment->created_by === $user->id);
    }
}
