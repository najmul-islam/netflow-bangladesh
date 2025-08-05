<?php

namespace App\Policies;

use App\Models\BatchAssessmentAttempt;
use App\Models\User;

class BatchAssessmentAttemptPolicy
{
    public function viewAny(User $user): bool
    {
        // Instructors and admins can view all attempts, students their own
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, BatchAssessmentAttempt $batchAssessmentAttempt): bool
    {
        // Students can only view their own attempts
        if ($user->hasRole('student')) {
            return $batchAssessmentAttempt->user_id === $user->user_id;
        }
        
        // Instructors can view attempts for their batches
        if ($user->hasRole('instructor')) {
            return $batchAssessmentAttempt->batchAssessment && 
                   $batchAssessmentAttempt->batchAssessment->batch &&
                   $batchAssessmentAttempt->batchAssessment->batch->instructors->contains($user->user_id);
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Students can create attempts, others through system
        return $user->hasRole('student');
    }

    public function update(User $user, BatchAssessmentAttempt $batchAssessmentAttempt): bool
    {
        // Students can update ongoing attempts, instructors can grade
        if ($user->hasRole('student')) {
            return $batchAssessmentAttempt->user_id === $user->user_id && 
                   $batchAssessmentAttempt->status === 'in_progress';
        }
        
        if ($user->hasRole('instructor')) {
            return $batchAssessmentAttempt->batchAssessment && 
                   $batchAssessmentAttempt->batchAssessment->batch &&
                   $batchAssessmentAttempt->batchAssessment->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, BatchAssessmentAttempt $batchAssessmentAttempt): bool
    {
        // Only admins can delete attempts
        return $user->hasRole('admin');
    }

    public function restore(User $user, BatchAssessmentAttempt $batchAssessmentAttempt): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, BatchAssessmentAttempt $batchAssessmentAttempt): bool
    {
        return $user->hasRole('admin');
    }
}
