<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can manage super admins (e.g., create, view, update, delete).
     * Only a super_admin can do this.
     */
    public function isSuperAdmin(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can manage supervisors.
     * Only a super_admin can do this.
     */
    public function isSupervisor(User $user): bool
    {
        return $user->isSupervisor();
    }

    /**
     * Determine whether the user can manage caregivers.
     * Both super_admin and supervisor can do this.
     */
    public function isCaregiver(User $user): bool
    {
        return $user->isCaregiver();
    }

    // Example of a more specific policy method
    // public function viewAny(User $user): bool { return $user->isSuperAdmin(); }
    // public function view(User $user, User $model): bool { return $user->isSuperAdmin() || $user->isSupervisor(); }
    // public function create(User $user): bool { return $user->isSuperAdmin(); }
    // public function update(User $user, User $model): bool { return $user->isSuperAdmin(); }
    // public function delete(User $user, User $model): bool { return $user->isSuperAdmin(); }
}
