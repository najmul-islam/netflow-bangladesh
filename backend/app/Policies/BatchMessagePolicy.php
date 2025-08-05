<?php

namespace App\Policies;

use App\Models\BatchMessage;
use App\Models\User;

class BatchMessagePolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view messages
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, BatchMessage $batchMessage): bool
    {
        // Users can view messages they sent or received
        if ($user->hasRole('student')) {
            return $batchMessage->sender_id === $user->user_id || 
                   $batchMessage->recipient_id === $user->user_id ||
                   ($batchMessage->batch && 
                    $batchMessage->batch->enrollments->where('user_id', $user->user_id)->isNotEmpty());
        }
        
        // Instructors can view messages in their batches
        if ($user->hasRole('instructor')) {
            return $batchMessage->sender_id === $user->user_id || 
                   $batchMessage->recipient_id === $user->user_id ||
                   ($batchMessage->batch && 
                    $batchMessage->batch->instructors->contains($user->user_id));
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // All users can send messages
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function update(User $user, BatchMessage $batchMessage): bool
    {
        // Users can only update their own messages before they're read
        return $batchMessage->sender_id === $user->user_id && 
               $batchMessage->read_at === null;
    }

    public function delete(User $user, BatchMessage $batchMessage): bool
    {
        // Users can delete their own messages
        if ($user->hasRole('student') || $user->hasRole('instructor')) {
            return $batchMessage->sender_id === $user->user_id;
        }
        
        return $user->hasRole('admin');
    }

    public function restore(User $user, BatchMessage $batchMessage): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, BatchMessage $batchMessage): bool
    {
        return $user->hasRole('admin');
    }
}
