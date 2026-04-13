<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** Only administrators can view the user list */
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    /** Only administrators can view a user detail */
    public function view(User $user, User $model): bool
    {
        return $user->isAdministrator();
    }

    /** Only administrators can create users */
    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    /** Only administrators can edit users */
    public function update(User $user, User $model): bool
    {
        return $user->isAdministrator();
    }

    /** Only administrators can delete — cannot delete themselves */
    public function delete(User $user, User $model): bool
    {
        return $user->isAdministrator() && $user->id !== $model->id;
    }
}
