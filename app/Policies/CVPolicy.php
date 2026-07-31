<?php

namespace App\Policies;

use App\Models\CV;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CVPolicy
{
    public function view(User $user, CV $cv): bool
    {
        return $user->id === $cv->user_id;
    }

    public function update(User $user, CV $cv): bool
    {
        return $user->id === $cv->user_id;
    }

    public function delete(User $user, CV $cv): bool
    {
        return $user->id === $cv->user_id;
    }
}