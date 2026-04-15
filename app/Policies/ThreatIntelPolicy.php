<?php

namespace App\Policies;

use App\Models\ThreatIntelItem;
use App\Models\User;

class ThreatIntelPolicy
{
    /** Any authenticated user may browse the threat intel feed. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Any authenticated user may add intel items. */
    public function create(User $user): bool
    {
        return true;
    }

    /** Any authenticated user may change an item's status (collaborative triage). */
    public function updateStatus(User $user, ThreatIntelItem $item): bool
    {
        return true;
    }

    /** Only the owner or an administrator may delete an intel item. */
    public function delete(User $user, ThreatIntelItem $item): bool
    {
        return $user->isAdministrator() || $item->created_by === $user->id;
    }

    /** Only administrators may bulk-import items. */
    public function import(User $user): bool
    {
        return $user->isAdministrator();
    }
}
