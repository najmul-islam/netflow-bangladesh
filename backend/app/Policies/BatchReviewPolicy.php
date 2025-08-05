<?php

namespace App\Policies;

use App\Models\BatchReview;
use App\Models\User;

class BatchReviewPolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view reviews
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, BatchReview $batchReview): bool
    {
        // Students can view reviews for batches they've completed
        if ($user->hasRole('student')) {
            return $batchReview->user_id === $user->user_id ||
                   ($batchReview->batch && 
                    $batchReview->batch->enrollments->where('user_id', $user->user_id)->where('status', 'completed')->isNotEmpty());
        }
        
        // Instructors can view reviews for their batches
        if ($user->hasRole('instructor')) {
            return $batchReview->batch && 
                   $batchReview->batch->instructors->contains($user->user_id);
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Only students who completed the batch can create reviews
        return $user->hasRole('student');
    }

    public function update(User $user, BatchReview $batchReview): bool
    {
        // Students can update their own reviews within a time limit
        if ($user->hasRole('student')) {
            return $batchReview->user_id === $user->user_id &&
                   $batchReview->created_at->diffInDays(now()) <= 7;
        }
        
        // Instructors can respond to reviews
        if ($user->hasRole('instructor')) {
            return $batchReview->batch && 
                   $batchReview->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, BatchReview $batchReview): bool
    {
        // Students can delete their own reviews, instructors can hide inappropriate ones
        if ($user->hasRole('student')) {
            return $batchReview->user_id === $user->user_id;
        }
        
        if ($user->hasRole('instructor')) {
            return $batchReview->batch && 
                   $batchReview->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function restore(User $user, BatchReview $batchReview): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, BatchReview $batchReview): bool
    {
        return $user->hasRole('admin');
    }
}
