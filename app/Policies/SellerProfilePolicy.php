<?php

namespace App\Policies;

use App\Models\SellerProfile;
use App\Models\User;

class SellerProfilePolicy
{
    public function update(User $user, SellerProfile $profile): bool
    {
        return $user->id === $profile->user_id && $user->canAuthenticate() && $user->hasVerifiedEmail();
    }
}
