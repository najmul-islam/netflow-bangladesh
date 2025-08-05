<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        // Instructors and admins can view users
        return $user->hasAnyRole(['instructor', 'admin']);
    }

    public function view(User $user, User $model): bool
    {
        // Users can view their own profile
        if ($user->user_id === $model->user_id) {
            return true;
        }
        
        // Instructors can view students in their batches
        if ($user->hasRole('instructor')) {
            return $model->enrollments->whereHas('batch.instructors', function($query) use ($user) {
                $query->where('user_id', $user->user_id);
            })->isNotEmpty();
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Only admins can create users
        return $user->hasRole('admin');
    }

    public function update(User $user, User $model): bool
    {
        // Users can update their own profile
        if ($user->user_id === $model->user_id) {
            return true;
        }
        
        // Only admins can update other users
        return $user->hasRole('admin');
    }

    public function delete(User $user, User $model): bool
    {
        // Users cannot delete themselves, only admins can delete users
        return $user->hasRole('admin') && $user->user_id !== $model->user_id;
    }

    public function restore(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasRole('admin') && $user->user_id !== $model->user_id;
    }
}
