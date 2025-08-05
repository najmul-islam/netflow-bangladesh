<?php

namespace App\Policies;

use App\Models\Module;
use App\Models\User;

class ModulePolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view modules
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, Module $module): bool
    {
        // Students can view modules from courses they're enrolled in
        if ($user->hasRole('student')) {
            return $module->course &&
                   $module->course->batches()
                       ->whereHas('enrollments', function($query) use ($user) {
                           $query->where('user_id', $user->user_id);
                       })->exists();
        }
        
        // Instructors can view modules from courses they teach
        if ($user->hasRole('instructor')) {
            return $module->course &&
                   $module->course->batches()
                       ->whereHas('instructors', function($query) use ($user) {
                           $query->where('user_id', $user->user_id);
                       })->exists();
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Only instructors and admins can create modules
        return $user->hasAnyRole(['instructor', 'admin']);
    }

    public function update(User $user, Module $module): bool
    {
        // Instructors can update modules for courses they teach
        if ($user->hasRole('instructor')) {
            return $module->course &&
                   $module->course->batches()
                       ->whereHas('instructors', function($query) use ($user) {
                           $query->where('user_id', $user->user_id);
                       })->exists();
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, Module $module): bool
    {
        // Instructors can delete modules for courses they teach (if no lessons exist)
        if ($user->hasRole('instructor')) {
            return $module->course &&
                   $module->course->batches()
                       ->whereHas('instructors', function($query) use ($user) {
                           $query->where('user_id', $user->user_id);
                       })->exists() &&
                   $module->lessons->isEmpty();
        }
        
        return $user->hasRole('admin');
    }

    public function restore(User $user, Module $module): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Module $module): bool
    {
        return $user->hasRole('admin');
    }
}
