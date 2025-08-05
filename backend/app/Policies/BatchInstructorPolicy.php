<?php

namespace App\Policies;

use App\Models\BatchInstructor;
use App\Models\User;

class BatchInstructorPolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view batch instructors
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, BatchInstructor $batchInstructor): bool
    {
        // Students can view instructors for their enrolled batches
        if ($user->hasRole('student')) {
            return $batchInstructor->batch && 
                   $batchInstructor->batch->enrollments->where('user_id', $user->user_id)->isNotEmpty();
        }
        
        // Instructors can view themselves and other instructors in their batches
        if ($user->hasRole('instructor')) {
            return $batchInstructor->user_id === $user->user_id ||
                   ($batchInstructor->batch && 
                    $batchInstructor->batch->instructors->contains($user->user_id));
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Only admins can assign instructors to batches
        return $user->hasRole('admin');
    }

    public function update(User $user, BatchInstructor $batchInstructor): bool
    {
        // Only admins can update instructor assignments
        return $user->hasRole('admin');
    }

    public function delete(User $user, BatchInstructor $batchInstructor): bool
    {
        // Only admins can remove instructor assignments
        return $user->hasRole('admin');
    }

    public function restore(User $user, BatchInstructor $batchInstructor): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, BatchInstructor $batchInstructor): bool
    {
        return $user->hasRole('admin');
    }
}
