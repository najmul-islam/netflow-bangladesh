<?php

namespace App\Policies;

use App\Models\CourseBatch;
use App\Models\User;

class CourseBatchPolicy
{
    public function viewAny(User $user): bool
    {
        // All authenticated users can view batches
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, CourseBatch $courseBatch): bool
    {
        // Students can view batches they are enrolled in or can enroll in
        if ($user->hasRole('student')) {
            // Can view if enrolled or if batch is open for enrollment
            return $courseBatch->enrollments->where('user_id', $user->user_id)->isNotEmpty() ||
                   $courseBatch->status === 'open';
        }
        
        // Instructors can view batches they are assigned to
        if ($user->hasRole('instructor')) {
            return $courseBatch->instructors->contains($user->user_id);
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Only instructors and admins can create batches
        return $user->hasAnyRole(['instructor', 'admin']);
    }

    public function update(User $user, CourseBatch $courseBatch): bool
    {
        // Instructors can update their assigned batches
        if ($user->hasRole('instructor')) {
            return $courseBatch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, CourseBatch $courseBatch): bool
    {
        // Instructors can delete their batches if no students are enrolled
        if ($user->hasRole('instructor')) {
            return $courseBatch->instructors->contains($user->user_id) &&
                   $courseBatch->enrollments->isEmpty();
        }
        
        return $user->hasRole('admin');
    }

    public function restore(User $user, CourseBatch $courseBatch): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, CourseBatch $courseBatch): bool
    {
        return $user->hasRole('admin');
    }
}
