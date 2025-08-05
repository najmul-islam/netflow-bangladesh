<?php

namespace App\Policies;

use App\Models\BatchActivityLog;
use App\Models\User;

class BatchActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        // Only instructors and admins can view activity logs
        return $user->hasAnyRole(['instructor', 'admin']);
    }

    public function view(User $user, BatchActivityLog $batchActivityLog): bool
    {
        // Admins can view all, instructors can view logs for their batches
        return $user->hasRole('admin') || 
               ($batchActivityLog->batch && $batchActivityLog->batch->instructors->contains($user->user_id));
    }

    public function create(User $user): bool
    {
        // System generates logs automatically
        return false;
    }

    public function update(User $user, BatchActivityLog $batchActivityLog): bool
    {
        // Activity logs should not be updated
        return false;
    }

    public function delete(User $user, BatchActivityLog $batchActivityLog): bool
    {
        // Only admins can delete logs for cleanup
        return $user->hasRole('admin');
    }

    public function restore(User $user, BatchActivityLog $batchActivityLog): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, BatchActivityLog $batchActivityLog): bool
    {
        return $user->hasRole('admin');
    }
}
