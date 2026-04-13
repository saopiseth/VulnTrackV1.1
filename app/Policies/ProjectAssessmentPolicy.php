<?php

namespace App\Policies;

use App\Models\ProjectAssessment;
use App\Models\User;

class ProjectAssessmentPolicy
{
    /** Anyone authenticated can view the list */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Anyone authenticated can view a single record */
    public function view(User $user, ProjectAssessment $assessment): bool
    {
        return true;
    }

    /** Anyone authenticated can create */
    public function create(User $user): bool
    {
        return true;
    }

    /** Only administrators or the record owner can edit */
    public function update(User $user, ProjectAssessment $assessment): bool
    {
        return $user->isAdministrator() || $user->id === $assessment->created_by;
    }

    /** Only administrators can delete */
    public function delete(User $user, ProjectAssessment $assessment): bool
    {
        return $user->isAdministrator();
    }
}
