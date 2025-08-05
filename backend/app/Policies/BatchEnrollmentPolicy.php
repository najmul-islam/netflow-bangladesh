<?php

namespace App\Policies;

use App\Models\BatchEnrollment;
use App\Models\User;

class BatchEnrollmentPolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view enrollments
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, BatchEnrollment $batchEnrollment): bool
    {
        // Students can only view their own enrollments
        if ($user->hasRole('student')) {
            return $batchEnrollment->user_id === $user->user_id;
        }
        
        // Instructors can view enrollments for their batches
        if ($user->hasRole('instructor')) {
            return $batchEnrollment->batch && 
                   $batchEnrollment->batch->instructors->contains($user->user_id);
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Students can enroll themselves, instructors and admins can enroll others
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function update(User $user, BatchEnrollment $batchEnrollment): bool
    {
        // Students can update their own enrollment status
        if ($user->hasRole('student')) {
            return $batchEnrollment->user_id === $user->user_id;
        }
        
        // Instructors can update enrollments for their batches
        if ($user->hasRole('instructor')) {
            return $batchEnrollment->batch && 
                   $batchEnrollment->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, BatchEnrollment $batchEnrollment): bool
    {
        // Students can unenroll themselves
        if ($user->hasRole('student')) {
            return $batchEnrollment->user_id === $user->user_id;
        }
        
        // Instructors can remove enrollments from their batches
        if ($user->hasRole('instructor')) {
            return $batchEnrollment->batch && 
                   $batchEnrollment->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function restore(User $user, BatchEnrollment $batchEnrollment): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, BatchEnrollment $batchEnrollment): bool
    {
        return $user->hasRole('admin');
    }
}
