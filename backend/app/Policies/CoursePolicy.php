<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    public function viewAny(User $user): bool
    {
        // All authenticated users can view courses
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, Course $course): bool
    {
        // All authenticated users can view individual courses
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function create(User $user): bool
    {
        // Only admins can create courses
        return $user->hasRole('admin');
    }

    public function update(User $user, Course $course): bool
    {
        // Only admins can update courses
        return $user->hasRole('admin');
    }

    public function delete(User $user, Course $course): bool
    {
        // Only admins can delete courses
        return $user->hasRole('admin');
    }

    public function restore(User $user, Course $course): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Course $course): bool
    {
        return $user->hasRole('admin');
    }
}
