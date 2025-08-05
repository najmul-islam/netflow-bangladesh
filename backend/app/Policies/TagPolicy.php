<?php
// filepath: app/Policies/TagPolicy.php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // All users can view tags
    }

    public function view(User $user, Tag $tag): bool
    {
        return true; // All users can view individual tags
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->hasRole('admin') && !$tag->courses()->exists();
    }
}