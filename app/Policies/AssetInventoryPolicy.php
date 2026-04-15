<?php

namespace App\Policies;

use App\Models\AssetInventory;
use App\Models\User;

class AssetInventoryPolicy
{
    /** Any authenticated user may browse the inventory. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Any authenticated user may view a single asset. */
    public function view(User $user, AssetInventory $asset): bool
    {
        return true;
    }

    /** Any authenticated user may add an asset. */
    public function create(User $user): bool
    {
        return true;
    }

    /** Any authenticated user may update asset details (classify, override OS, etc.). */
    public function update(User $user, AssetInventory $asset): bool
    {
        return true;
    }

    /** Only the owner or an administrator may delete an asset record. */
    public function delete(User $user, AssetInventory $asset): bool
    {
        return $user->isAdministrator() || $asset->created_by === $user->id;
    }
}
