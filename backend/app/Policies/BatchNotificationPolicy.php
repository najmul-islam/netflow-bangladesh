<?php

namespace App\Policies;

use App\Models\BatchNotification;
use App\Models\User;

class BatchNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view notifications
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, BatchNotification $batchNotification): bool
    {
        // Users can only view their own notifications
        if ($user->hasRole('student')) {
            return $batchNotification->user_id === $user->user_id;
        }
        
        // Instructors can view notifications for their batches or their own
        if ($user->hasRole('instructor')) {
            return $batchNotification->user_id === $user->user_id ||
                   ($batchNotification->batch && 
                    $batchNotification->batch->instructors->contains($user->user_id));
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Instructors and admins can create notifications
        return $user->hasAnyRole(['instructor', 'admin']);
    }

    public function update(User $user, BatchNotification $batchNotification): bool
    {
        // Users can mark their own notifications as read
        if ($user->hasRole('student')) {
            return $batchNotification->user_id === $user->user_id;
        }
        
        // Instructors can update notifications for their batches
        if ($user->hasRole('instructor')) {
            return $batchNotification->batch && 
                   $batchNotification->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, BatchNotification $batchNotification): bool
    {
        // Users can delete their own notifications
        if ($user->hasRole('student') || $user->hasRole('instructor')) {
            return $batchNotification->user_id === $user->user_id;
        }
        
        return $user->hasRole('admin');
    }

    public function restore(User $user, BatchNotification $batchNotification): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, BatchNotification $batchNotification): bool
    {
        return $user->hasRole('admin');
    }
}
