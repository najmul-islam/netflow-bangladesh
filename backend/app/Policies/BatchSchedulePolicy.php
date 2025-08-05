<?php

namespace App\Policies;

use App\Models\BatchSchedule;
use App\Models\User;

class BatchSchedulePolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view batch schedules
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, BatchSchedule $batchSchedule): bool
    {
        // Students can view schedules for their enrolled batches
        if ($user->hasRole('student')) {
            return $batchSchedule->batch && 
                   $batchSchedule->batch->enrollments->where('user_id', $user->user_id)->isNotEmpty();
        }
        
        // Instructors can view schedules for their batches
        if ($user->hasRole('instructor')) {
            return $batchSchedule->batch && 
                   $batchSchedule->batch->instructors->contains($user->user_id);
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Only instructors and admins can create schedules
        return $user->hasAnyRole(['instructor', 'admin']);
    }

    public function update(User $user, BatchSchedule $batchSchedule): bool
    {
        // Instructors can update schedules for their batches
        if ($user->hasRole('instructor')) {
            return $batchSchedule->batch && 
                   $batchSchedule->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, BatchSchedule $batchSchedule): bool
    {
        // Instructors can delete schedules for their batches
        if ($user->hasRole('instructor')) {
            return $batchSchedule->batch && 
                   $batchSchedule->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function restore(User $user, BatchSchedule $batchSchedule): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, BatchSchedule $batchSchedule): bool
    {
        return $user->hasRole('admin');
    }
}
