<?php

namespace App\Policies;

use App\Models\BatchLessonProgress;
use App\Models\User;

class BatchLessonProgressPolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view lesson progress
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, BatchLessonProgress $batchLessonProgress): bool
    {
        // Students can view their own progress
        if ($user->hasRole('student')) {
            return $batchLessonProgress->user_id === $user->user_id;
        }
        
        // Instructors can view progress for their batch students
        if ($user->hasRole('instructor')) {
            return $batchLessonProgress->batch && 
                   $batchLessonProgress->batch->instructors->contains($user->user_id);
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Students create their own progress, instructors and admins can create for others
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function update(User $user, BatchLessonProgress $batchLessonProgress): bool
    {
        // Students can update their own progress
        if ($user->hasRole('student')) {
            return $batchLessonProgress->user_id === $user->user_id;
        }
        
        // Instructors can update progress for their batch students
        if ($user->hasRole('instructor')) {
            return $batchLessonProgress->batch && 
                   $batchLessonProgress->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, BatchLessonProgress $batchLessonProgress): bool
    {
        // Only instructors and admins can delete progress records
        if ($user->hasRole('instructor')) {
            return $batchLessonProgress->batch && 
                   $batchLessonProgress->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function restore(User $user, BatchLessonProgress $batchLessonProgress): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, BatchLessonProgress $batchLessonProgress): bool
    {
        return $user->hasRole('admin');
    }
}
