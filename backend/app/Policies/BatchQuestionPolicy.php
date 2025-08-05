<?php

namespace App\Policies;

use App\Models\BatchQuestion;
use App\Models\User;

class BatchQuestionPolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view questions
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, BatchQuestion $batchQuestion): bool
    {
        // Students can view questions for their enrolled batches during assessments
        if ($user->hasRole('student')) {
            return $batchQuestion->assessment && 
                   $batchQuestion->assessment->batch && 
                   $batchQuestion->assessment->batch->enrollments->where('user_id', $user->user_id)->isNotEmpty();
        }
        
        // Instructors can view questions for their batches
        if ($user->hasRole('instructor')) {
            return $batchQuestion->assessment && 
                   $batchQuestion->assessment->batch && 
                   $batchQuestion->assessment->batch->instructors->contains($user->user_id);
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Only instructors and admins can create questions
        return $user->hasAnyRole(['instructor', 'admin']);
    }

    public function update(User $user, BatchQuestion $batchQuestion): bool
    {
        // Instructors can update questions for their batches
        if ($user->hasRole('instructor')) {
            return $batchQuestion->assessment && 
                   $batchQuestion->assessment->batch && 
                   $batchQuestion->assessment->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, BatchQuestion $batchQuestion): bool
    {
        // Instructors can delete questions for their batches if no responses exist
        if ($user->hasRole('instructor')) {
            return $batchQuestion->assessment && 
                   $batchQuestion->assessment->batch && 
                   $batchQuestion->assessment->batch->instructors->contains($user->user_id) &&
                   $batchQuestion->responses->isEmpty();
        }
        
        return $user->hasRole('admin');
    }

    public function restore(User $user, BatchQuestion $batchQuestion): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, BatchQuestion $batchQuestion): bool
    {
        return $user->hasRole('admin');
    }
}
