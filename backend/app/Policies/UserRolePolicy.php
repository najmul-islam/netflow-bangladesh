<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserRole;

class UserRolePolicy
{
    public function viewAny(User $user): bool
    {
        // Only admins can view user role assignments
        return $user->hasRole('admin');
    }

    public function view(User $user, UserRole $userRole): bool
    {
        // Users can view their own role assignments
        if ($user->user_id === $userRole->user_id) {
            return true;
        }
        
        // Only admins can view other user role assignments
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Only admins can assign roles to users
        return $user->hasRole('admin');
    }

    public function update(User $user, UserRole $userRole): bool
    {
        // Only admins can update user role assignments
        return $user->hasRole('admin');
    }

    public function delete(User $user, UserRole $userRole): bool
    {
        // Only admins can remove role assignments
        return $user->hasRole('admin');
    }

    public function restore(User $user, UserRole $userRole): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, UserRole $userRole): bool
    {
        return $user->hasRole('admin');
    }
}
