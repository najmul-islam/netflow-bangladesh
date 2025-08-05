<?php

namespace App\Policies;

use App\Models\ClassAttendance;
use App\Models\User;

class ClassAttendancePolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view attendance records
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, ClassAttendance $attendance): bool
    {
        // Students can only view their own attendance
        if ($user->hasRole('student')) {
            return $attendance->user_id === $user->user_id;
        }
        
        // Instructors can view attendance for their classes
        if ($user->hasRole('instructor')) {
            // Check if instructor teaches this class/batch
            return $attendance->class && 
                   $attendance->class->batch &&
                   $attendance->class->batch->instructors->contains($user->user_id);
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Only instructors and admins can create attendance records
        return $user->hasAnyRole(['instructor', 'admin']);
    }

    public function update(User $user, ClassAttendance $attendance): bool
    {
        // Instructors can update attendance for their classes
        if ($user->hasRole('instructor')) {
            return $attendance->class && 
                   $attendance->class->batch &&
                   $attendance->class->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, ClassAttendance $attendance): bool
    {
        // Instructors can delete attendance for their classes
        if ($user->hasRole('instructor')) {
            return $attendance->class && 
                   $attendance->class->batch &&
                   $attendance->class->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function restore(User $user, ClassAttendance $attendance): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, ClassAttendance $attendance): bool
    {
        return $user->hasRole('admin');
    }
}
