<?php

namespace App\Policies;

use App\Models\BatchForumTopic;
use App\Models\User;

class BatchForumTopicPolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view forum topics
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, BatchForumTopic $batchForumTopic): bool
    {
        // Students can view topics for their enrolled batches
        if ($user->hasRole('student')) {
            return $batchForumTopic->batch && 
                   $batchForumTopic->batch->enrollments->where('user_id', $user->user_id)->isNotEmpty();
        }
        
        // Instructors can view topics for their batches
        if ($user->hasRole('instructor')) {
            return $batchForumTopic->batch && 
                   $batchForumTopic->batch->instructors->contains($user->user_id);
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // All enrolled users can create topics
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function update(User $user, BatchForumTopic $batchForumTopic): bool
    {
        // Users can only update their own topics
        if ($user->hasRole('student')) {
            return $batchForumTopic->user_id === $user->user_id;
        }
        
        // Instructors can update topics in their batches
        if ($user->hasRole('instructor')) {
            return $batchForumTopic->user_id === $user->user_id || 
                   ($batchForumTopic->batch && 
                    $batchForumTopic->batch->instructors->contains($user->user_id));
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, BatchForumTopic $batchForumTopic): bool
    {
        // Users can delete their own topics
        if ($user->hasRole('student')) {
            return $batchForumTopic->user_id === $user->user_id;
        }
        
        // Instructors can delete topics in their batches
        if ($user->hasRole('instructor')) {
            return $batchForumTopic->user_id === $user->user_id || 
                   ($batchForumTopic->batch && 
                    $batchForumTopic->batch->instructors->contains($user->user_id));
        }
        
        return $user->hasRole('admin');
    }

    public function restore(User $user, BatchForumTopic $batchForumTopic): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, BatchForumTopic $batchForumTopic): bool
    {
        return $user->hasRole('admin');
    }
}
