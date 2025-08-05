<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view lessons
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, Lesson $lesson): bool
    {
        // Students can view lessons from courses they're enrolled in
        if ($user->hasRole('student')) {
            return $lesson->module && 
                   $lesson->module->course &&
                   $lesson->module->course->batches()
                       ->whereHas('enrollments', function($query) use ($user) {
                           $query->where('user_id', $user->user_id);
                       })->exists();
        }
        
        // Instructors can view lessons from courses they teach
        if ($user->hasRole('instructor')) {
            return $lesson->module && 
                   $lesson->module->course &&
                   $lesson->module->course->batches()
                       ->whereHas('instructors', function($query) use ($user) {
                           $query->where('user_id', $user->user_id);
                       })->exists();
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Only instructors and admins can create lessons
        return $user->hasAnyRole(['instructor', 'admin']);
    }

    public function update(User $user, Lesson $lesson): bool
    {
        // Instructors can update lessons for courses they teach
        if ($user->hasRole('instructor')) {
            return $lesson->module && 
                   $lesson->module->course &&
                   $lesson->module->course->batches()
                       ->whereHas('instructors', function($query) use ($user) {
                           $query->where('user_id', $user->user_id);
                       })->exists();
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, Lesson $lesson): bool
    {
        // Instructors can delete lessons for courses they teach
        if ($user->hasRole('instructor')) {
            return $lesson->module && 
                   $lesson->module->course &&
                   $lesson->module->course->batches()
                       ->whereHas('instructors', function($query) use ($user) {
                           $query->where('user_id', $user->user_id);
                       })->exists();
        }
        
        return $user->hasRole('admin');
    }

    public function restore(User $user, Lesson $lesson): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Lesson $lesson): bool
    {
        return $user->hasRole('admin');
    }
}
