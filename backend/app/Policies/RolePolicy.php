<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        // Instructors and admins can view roles
        return $user->hasAnyRole(['instructor', 'admin']);
    }

    public function view(User $user, Role $role): bool
    {
        // Instructors and admins can view role details
        return $user->hasAnyRole(['instructor', 'admin']);
    }

    public function create(User $user): bool
    {
        // Only admins can create roles
        return $user->hasRole('admin');
    }

    public function update(User $user, Role $role): bool
    {
        // Only admins can update roles
        return $user->hasRole('admin');
    }

    public function delete(User $user, Role $role): bool
    {
        // Only admins can delete roles if no users are assigned
        return $user->hasRole('admin') && $role->users->isEmpty();
    }

    public function restore(User $user, Role $role): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Role $role): bool
    {
        return $user->hasRole('admin');
    }
}
