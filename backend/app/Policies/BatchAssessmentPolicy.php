<?php

namespace App\Policies;

use App\Models\BatchAssessment;
use App\Models\User;

class BatchAssessmentPolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view assessments
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, BatchAssessment $batchAssessment): bool
    {
        // Students can view assessments for their enrolled batches
        if ($user->hasRole('student')) {
            return $batchAssessment->batch && 
                   $batchAssessment->batch->enrollments->where('user_id', $user->user_id)->isNotEmpty();
        }
        
        // Instructors can view assessments for their batches
        if ($user->hasRole('instructor')) {
            return $batchAssessment->batch && 
                   $batchAssessment->batch->instructors->contains($user->user_id);
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Only instructors and admins can create assessments
        return $user->hasAnyRole(['instructor', 'admin']);
    }

    public function update(User $user, BatchAssessment $batchAssessment): bool
    {
        // Instructors can update assessments for their batches
        if ($user->hasRole('instructor')) {
            return $batchAssessment->batch && 
                   $batchAssessment->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, BatchAssessment $batchAssessment): bool
    {
        // Instructors can delete assessments for their batches if no attempts
        if ($user->hasRole('instructor')) {
            return $batchAssessment->batch && 
                   $batchAssessment->batch->instructors->contains($user->user_id) &&
                   $batchAssessment->attempts->isEmpty();
        }
        
        return $user->hasRole('admin');
    }

    public function restore(User $user, BatchAssessment $batchAssessment): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, BatchAssessment $batchAssessment): bool
    {
        return $user->hasRole('admin');
    }
}
