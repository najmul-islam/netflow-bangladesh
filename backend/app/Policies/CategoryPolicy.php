<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        // All users can view categories
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function view(User $user, Category $category): bool
    {
        // All users can view individual categories
        return $user->hasAnyRole(['student', 'instructor', 'admin']);
    }

    public function create(User $user): bool
    {
        // Only admins can create categories
        return $user->hasRole('admin');
    }

    public function update(User $user, Category $category): bool
    {
        // Only admins can update categories
        return $user->hasRole('admin');
    }

    public function delete(User $user, Category $category): bool
    {
        // Only admins can delete categories if no courses are using them
        return $user->hasRole('admin') && $category->courses->isEmpty();
    }

    public function restore(User $user, Category $category): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, Category $category): bool
    {
        return $user->hasRole('admin');
    }
}
