<?php

namespace App\Policies;

use App\Models\BatchQuestionOption;
use App\Models\User;

class BatchQuestionOptionPolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view question options
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, BatchQuestionOption $batchQuestionOption): bool
    {
        // Students can view options for questions in their enrolled batches
        if ($user->hasRole('student')) {
            return $batchQuestionOption->question && 
                   $batchQuestionOption->question->assessment && 
                   $batchQuestionOption->question->assessment->batch && 
                   $batchQuestionOption->question->assessment->batch->enrollments->where('user_id', $user->user_id)->isNotEmpty();
        }
        
        // Instructors can view options for their batches
        if ($user->hasRole('instructor')) {
            return $batchQuestionOption->question && 
                   $batchQuestionOption->question->assessment && 
                   $batchQuestionOption->question->assessment->batch && 
                   $batchQuestionOption->question->assessment->batch->instructors->contains($user->user_id);
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Only instructors and admins can create question options
        return $user->hasAnyRole(['instructor', 'admin']);
    }

    public function update(User $user, BatchQuestionOption $batchQuestionOption): bool
    {
        // Instructors can update options for their batches
        if ($user->hasRole('instructor')) {
            return $batchQuestionOption->question && 
                   $batchQuestionOption->question->assessment && 
                   $batchQuestionOption->question->assessment->batch && 
                   $batchQuestionOption->question->assessment->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, BatchQuestionOption $batchQuestionOption): bool
    {
        // Instructors can delete options for their batches if no responses exist
        if ($user->hasRole('instructor')) {
            return $batchQuestionOption->question && 
                   $batchQuestionOption->question->assessment && 
                   $batchQuestionOption->question->assessment->batch && 
                   $batchQuestionOption->question->assessment->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function restore(User $user, BatchQuestionOption $batchQuestionOption): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, BatchQuestionOption $batchQuestionOption): bool
    {
        return $user->hasRole('admin');
    }
}
