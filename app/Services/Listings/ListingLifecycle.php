<?php

namespace App\Services\Listings;

use App\Models\Listing;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ListingLifecycle
{
    public function __construct(private DraftValidator $validator) {}

    public function profileReady(Listing $listing): bool
    {
        return SellerProfile::where('user_id', $listing->user_id)->where('is_public', true)->exists();
    }

    public function actions(Listing $listing): array
    {
        if (! in_array($listing->moderation_state, ['unreviewed', 'reviewed'], true)) {
            return [];
        }

        return match ($listing->effectiveStatus()) {
            'draft' => ['remove'],
            'active' => ['edit', 'reserve', 'sold', 'remove'],
            'reserved' => ['edit', 'activate', 'sold', 'remove'],
            'expired', 'sold' => ['edit', 'renew', 'remove'],
            default => [],
        };
    }

    public function publish(User $actor, Listing $draft, int $version): Listing
    {
        return DB::transaction(function () use ($actor, $draft, $version) {
            $listing = $this->locked($actor, $draft, $version);
            Gate::forUser($actor->fresh())->authorize('updateDraft', $listing);
            $this->validatePublication($listing);
            $changes = ['status' => 'active', 'moderation_state' => 'unreviewed', 'last_step' => 'preview'];
            if (! $listing->published_at) {
                $changes['published_at'] = now();
            }
            // Editing a live listing does not extend its lifetime.
            if (! $listing->expires_at || $listing->expires_at->lte(now())) {
                $changes['expires_at'] = now()->addDays(config('listings.active_days'));
            }

            return $this->record($listing, $actor->id, 'publish', $changes);
        });
    }

    public function transition(User $actor, Listing $draft, string $action, int $version): Listing
    {
        return DB::transaction(function () use ($actor, $draft, $action, $version) {
            $listing = $this->locked($actor, $draft, $version);
            if (! in_array($action, $this->actions($listing), true)) {
                throw ValidationException::withMessages(['action' => __('market.action_unavailable')]);
            }
            $changes = match ($action) {
                'edit' => ['status' => 'draft', 'last_step' => 'details', 'moderation_state' => 'unreviewed'],
                'reserve' => ['status' => 'reserved'],
                'activate' => ['status' => 'active'],
                'sold' => ['status' => 'sold'],
                'remove' => ['status' => 'removed', 'removed_at' => now()],
                'renew' => ['status' => 'active', 'expires_at' => now()->addDays(config('listings.active_days')), 'renewed_at' => now(), 'published_at' => now(), 'moderation_state' => 'unreviewed'],
            };
            if (in_array($action, ['renew', 'activate'], true)) {
                $this->validatePublication($listing);
            }

            return $this->record($listing, $actor->id, $action, $changes);
        });
    }

    public function expire(int $id, int $owner): bool
    {
        return DB::transaction(function () use ($id, $owner) {
            User::whereKey($owner)->lockForUpdate()->firstOrFail();
            $listing = Listing::whereKey($id)->lockForUpdate()->first();
            if (! $listing || ! in_array($listing->status, ['active', 'reserved'], true) || ! $listing->expires_at?->lte(now())) {
                return false;
            }
            $this->record($listing, null, 'expire', ['status' => 'expired']);

            return true;
        });
    }

    private function validatePublication(Listing $listing): void
    {
        DB::select('SELECT pg_advisory_xact_lock_shared(782016)');
        if (! in_array($listing->moderation_state, ['unreviewed', 'reviewed'], true)) {
            throw ValidationException::withMessages(['moderation' => __('market.action_unavailable')]);
        }
        $issues = $this->validator->issues($listing);
        if (! $this->profileReady($listing)) {
            $issues['profile'] = ['profile' => [__('market.profile_required')]];
        }
        if ($issues) {
            $errors = [];
            foreach ($issues as $messages) {
                $errors = array_merge($errors, $messages);
            }
            throw ValidationException::withMessages($errors);
        }
        // Reapply validated values so obsolete attribute keys cannot become public.
        foreach (array_diff(DraftValidator::STEPS, ['preview']) as $step) {
            $listing->fill($this->validator->validate($listing, $step, $this->validator->inputFor($listing, $step), true));
        }
    }

    private function locked(User $actor, Listing $draft, int $version): Listing
    {
        $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
        $listing = Listing::whereKey($draft->id)->lockForUpdate()->firstOrFail();
        Gate::forUser($user)->authorize('manage', $listing);
        if ($listing->version !== $version) {
            throw new DraftConflict;
        }

        return $listing;
    }

    private function record(Listing $listing, ?int $actor, string $action, array $changes): Listing
    {
        $before = $listing->status;
        $listing->forceFill([...$changes, 'version' => $listing->version + 1])->save();
        DB::table('listing_events')->insert(['listing_id' => $listing->id, 'actor_id' => $actor, 'action' => $action, 'from_status' => $before, 'to_status' => $listing->status, 'version' => $listing->version, 'created_at' => now()]);

        return $listing;
    }
}
