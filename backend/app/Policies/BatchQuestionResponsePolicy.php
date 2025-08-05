<?php

namespace App\Policies;

use App\Models\BatchQuestionResponse;
use App\Models\User;

class BatchQuestionResponsePolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view question responses
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, BatchQuestionResponse $batchQuestionResponse): bool
    {
        // Students can only view their own responses
        if ($user->hasRole('student')) {
            return $batchQuestionResponse->attempt && 
                   $batchQuestionResponse->attempt->user_id === $user->user_id;
        }
        
        // Instructors can view responses for their batches
        if ($user->hasRole('instructor')) {
            return $batchQuestionResponse->attempt && 
                   $batchQuestionResponse->attempt->batchAssessment && 
                   $batchQuestionResponse->attempt->batchAssessment->batch && 
                   $batchQuestionResponse->attempt->batchAssessment->batch->instructors->contains($user->user_id);
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Students can create responses during attempts
        return $user->hasRole('student');
    }

    public function update(User $user, BatchQuestionResponse $batchQuestionResponse): bool
    {
        // Students can update their responses during active attempts
        if ($user->hasRole('student')) {
            return $batchQuestionResponse->attempt && 
                   $batchQuestionResponse->attempt->user_id === $user->user_id && 
                   $batchQuestionResponse->attempt->status === 'in_progress';
        }
        
        // Instructors can update scores/feedback
        if ($user->hasRole('instructor')) {
            return $batchQuestionResponse->attempt && 
                   $batchQuestionResponse->attempt->batchAssessment && 
                   $batchQuestionResponse->attempt->batchAssessment->batch && 
                   $batchQuestionResponse->attempt->batchAssessment->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, BatchQuestionResponse $batchQuestionResponse): bool
    {
        // Only admins can delete responses
        return $user->hasRole('admin');
    }

    public function restore(User $user, BatchQuestionResponse $batchQuestionResponse): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, BatchQuestionResponse $batchQuestionResponse): bool
    {
        return $user->hasRole('admin');
    }
}
