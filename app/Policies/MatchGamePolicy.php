<?php

namespace App\Policies;

use App\Models\MatchGame;
use App\Models\User;

class MatchGamePolicy
{
    public function enterResult(User $user, MatchGame $match): bool
    {
        return app(ResultPolicy::class)->enter($user, $match);
    }
}