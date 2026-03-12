<?php

namespace App\Policies;

use App\Models\Blog;
use App\Models\User;

class BlogPolicy
{
    public function delete(User $user, Blog $blog): bool
    {
        return $user->is_admin;
    }
}
