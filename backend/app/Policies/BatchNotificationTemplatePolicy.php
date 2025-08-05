<?php

namespace App\Policies;

use App\Models\BatchNotificationTemplate;
use App\Models\User;

class BatchNotificationTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        // Instructors and admins can view templates
        return $user->hasAnyRole(['instructor', 'admin']);
    }

    public function view(User $user, BatchNotificationTemplate $batchNotificationTemplate): bool
    {
        // Instructors can view templates for their batches
        if ($user->hasRole('instructor')) {
            return $batchNotificationTemplate->batch && 
                   $batchNotificationTemplate->batch->instructors->contains($user->user_id);
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Only instructors and admins can create templates
        return $user->hasAnyRole(['instructor', 'admin']);
    }

    public function update(User $user, BatchNotificationTemplate $batchNotificationTemplate): bool
    {
        // Instructors can update templates for their batches
        if ($user->hasRole('instructor')) {
            return $batchNotificationTemplate->batch && 
                   $batchNotificationTemplate->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, BatchNotificationTemplate $batchNotificationTemplate): bool
    {
        // Instructors can delete templates for their batches
        if ($user->hasRole('instructor')) {
            return $batchNotificationTemplate->batch && 
                   $batchNotificationTemplate->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function restore(User $user, BatchNotificationTemplate $batchNotificationTemplate): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, BatchNotificationTemplate $batchNotificationTemplate): bool
    {
        return $user->hasRole('admin');
    }
}
