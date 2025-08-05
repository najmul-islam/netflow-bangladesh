<?php

namespace App\Policies;

use App\Models\LessonResource;
use App\Models\User;

class LessonResourcePolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view lesson resources
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, LessonResource $lessonResource): bool
    {
        // Students can view resources from lessons they have access to
        if ($user->hasRole('student')) {
            return $lessonResource->lesson && 
                   $lessonResource->lesson->module &&
                   $lessonResource->lesson->module->course &&
                   $lessonResource->lesson->module->course->batches()
                       ->whereHas('enrollments', function($query) use ($user) {
                           $query->where('user_id', $user->user_id);
                       })->exists();
        }
        
        // Instructors can view resources from lessons they teach
        if ($user->hasRole('instructor')) {
            return $lessonResource->lesson && 
                   $lessonResource->lesson->module &&
                   $lessonResource->lesson->module->course &&
                   $lessonResource->lesson->module->course->batches()
                       ->whereHas('instructors', function($query) use ($user) {
                           $query->where('user_id', $user->user_id);
                       })->exists();
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Only instructors and admins can create lesson resources
        return $user->hasAnyRole(['instructor', 'admin']);
    }

    public function update(User $user, LessonResource $lessonResource): bool
    {
        // Instructors can update resources for lessons they teach
        if ($user->hasRole('instructor')) {
            return $lessonResource->lesson && 
                   $lessonResource->lesson->module &&
                   $lessonResource->lesson->module->course &&
                   $lessonResource->lesson->module->course->batches()
                       ->whereHas('instructors', function($query) use ($user) {
                           $query->where('user_id', $user->user_id);
                       })->exists();
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, LessonResource $lessonResource): bool
    {
        // Instructors can delete resources for lessons they teach
        if ($user->hasRole('instructor')) {
            return $lessonResource->lesson && 
                   $lessonResource->lesson->module &&
                   $lessonResource->lesson->module->course &&
                   $lessonResource->lesson->module->course->batches()
                       ->whereHas('instructors', function($query) use ($user) {
                           $query->where('user_id', $user->user_id);
                       })->exists();
        }
        
        return $user->hasRole('admin');
    }

    public function restore(User $user, LessonResource $lessonResource): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, LessonResource $lessonResource): bool
    {
        return $user->hasRole('admin');
    }
}
