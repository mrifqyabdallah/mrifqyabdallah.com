<?php

namespace App\Policies;

use App\Models\User;

class StatsPolicy
{
    public function viewOpcache(User $user): bool
    {
        return (bool) $user->is_admin;
    }
}
