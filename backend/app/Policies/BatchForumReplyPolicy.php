<?php

namespace App\Policies;

use App\Models\BatchForumReply;
use App\Models\User;

class BatchForumReplyPolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view forum replies
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, BatchForumReply $batchForumReply): bool
    {
        // Users can view replies if they can access the topic's batch
        if ($user->hasRole('student')) {
            return $batchForumReply->topic && 
                   $batchForumReply->topic->batch && 
                   $batchForumReply->topic->batch->enrollments->where('user_id', $user->user_id)->isNotEmpty();
        }
        
        if ($user->hasRole('instructor')) {
            return $batchForumReply->topic && 
                   $batchForumReply->topic->batch && 
                   $batchForumReply->topic->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // All enrolled users can create replies
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function update(User $user, BatchForumReply $batchForumReply): bool
    {
        // Users can only update their own replies
        if ($user->hasRole('student')) {
            return $batchForumReply->user_id === $user->user_id;
        }
        
        // Instructors can update replies in their batches
        if ($user->hasRole('instructor')) {
            return $batchForumReply->user_id === $user->user_id || 
                   ($batchForumReply->topic && 
                    $batchForumReply->topic->batch && 
                    $batchForumReply->topic->batch->instructors->contains($user->user_id));
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, BatchForumReply $batchForumReply): bool
    {
        // Users can delete their own replies
        if ($user->hasRole('student')) {
            return $batchForumReply->user_id === $user->user_id;
        }
        
        // Instructors can delete replies in their batches
        if ($user->hasRole('instructor')) {
            return $batchForumReply->user_id === $user->user_id || 
                   ($batchForumReply->topic && 
                    $batchForumReply->topic->batch && 
                    $batchForumReply->topic->batch->instructors->contains($user->user_id));
        }
        
        return $user->hasRole('admin');
    }

    public function restore(User $user, BatchForumReply $batchForumReply): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, BatchForumReply $batchForumReply): bool
    {
        return $user->hasRole('admin');
    }
}
