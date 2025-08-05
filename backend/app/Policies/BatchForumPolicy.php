<?php

namespace App\Policies;

use App\Models\BatchForum;
use App\Models\User;

class BatchForumPolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view forums
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, BatchForum $batchForum): bool
    {
        // Students can view forums for their enrolled batches
        if ($user->hasRole('student')) {
            return $batchForum->batch && 
                   $batchForum->batch->enrollments->where('user_id', $user->user_id)->isNotEmpty();
        }
        
        // Instructors can view forums for their batches
        if ($user->hasRole('instructor')) {
            return $batchForum->batch && 
                   $batchForum->batch->instructors->contains($user->user_id);
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Only instructors and admins can create forum instances
        return $user->hasAnyRole(['instructor', 'admin']);
    }

    public function update(User $user, BatchForum $batchForum): bool
    {
        // Instructors can update forums for their batches
        if ($user->hasRole('instructor')) {
            return $batchForum->batch && 
                   $batchForum->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, BatchForum $batchForum): bool
    {
        // Instructors can delete forums for their batches
        if ($user->hasRole('instructor')) {
            return $batchForum->batch && 
                   $batchForum->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function restore(User $user, BatchForum $batchForum): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, BatchForum $batchForum): bool
    {
        return $user->hasRole('admin');
    }
}
