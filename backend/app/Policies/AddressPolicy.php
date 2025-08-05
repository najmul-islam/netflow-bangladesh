<?php

namespace App\Policies;

use App\Models\Address;
use App\Models\User;

class AddressPolicy
{
    public function viewAny(User $user): bool
    {
        // Only admins can view all addresses
        return $user->hasRole('admin');
    }

    public function view(User $user, Address $address): bool
    {
        // Users can view their own address, admins can view any
        return $user->hasRole('admin') || $user->user_id === $address->user_id;
    }

    public function create(User $user): bool
    {
        // All authenticated users can create addresses
        return true;
    }

    public function update(User $user, Address $address): bool
    {
        // Users can update their own address, admins can update any
        return $user->hasRole('admin') || $user->user_id === $address->user_id;
    }

    public function delete(User $user, Address $address): bool
    {
        // Users can delete their own address, admins can delete any
        return $user->hasRole('admin') || $user->user_id === $address->user_id;
    }

    public function restore(User $user, Address $address): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Address $address): bool
    {
        return $user->hasRole('admin');
    }
}
