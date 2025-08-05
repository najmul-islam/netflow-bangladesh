<?php
// filepath: app/Policies/CourseInstructorPolicy.php

namespace App\Policies;

use App\Models\CourseInstructor;
use App\Models\User;

class CourseInstructorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['instructor', 'admin']);
    }

    public function view(User $user, CourseInstructor $courseInstructor): bool
    {
        if ($user->hasRole('instructor')) {
            return $courseInstructor->user_id === $user->user_id;
        }
        
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, CourseInstructor $courseInstructor): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, CourseInstructor $courseInstructor): bool
    {
        return $user->hasRole('admin');
    }

    public function restore(User $user, CourseInstructor $courseInstructor): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, CourseInstructor $courseInstructor): bool
    {
        return $user->hasRole('admin');
    }
}