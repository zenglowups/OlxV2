<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ListingPolicy
{
    public function create(User $user): bool
    {
        return $user->canAuthenticate() && $user->hasVerifiedEmail() && ! $user->is_admin;
    }

    public function updateDraft(User $user, Listing $listing): Response
    {
        return $this->create($user) && $listing->user_id === $user->id && $listing->status === 'draft'
            ? Response::allow() : Response::denyAsNotFound();
    }
}
