<?php

namespace App\Policies;

use App\Models\BatchCertificate;
use App\Models\User;

class BatchCertificatePolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view certificates
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, BatchCertificate $batchCertificate): bool
    {
        // Students can only view their own certificates
        if ($user->hasRole('student')) {
            return $batchCertificate->user_id === $user->user_id;
        }
        
        // Instructors can view certificates for their batches
        if ($user->hasRole('instructor')) {
            return $batchCertificate->batch && 
                   $batchCertificate->batch->instructors->contains($user->user_id);
        }
        
        // Admins can view all
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        // Only instructors and admins can create certificates
        return $user->hasAnyRole(['instructor', 'admin']);
    }

    public function update(User $user, BatchCertificate $batchCertificate): bool
    {
        // Instructors can update certificates for their batches
        if ($user->hasRole('instructor')) {
            return $batchCertificate->batch && 
                   $batchCertificate->batch->instructors->contains($user->user_id);
        }
        
        return $user->hasRole('admin');
    }

    public function delete(User $user, BatchCertificate $batchCertificate): bool
    {
        // Only admins can delete certificates
        return $user->hasRole('admin');
    }

    public function restore(User $user, BatchCertificate $batchCertificate): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, BatchCertificate $batchCertificate): bool
    {
        return $user->hasRole('admin');
    }
}
