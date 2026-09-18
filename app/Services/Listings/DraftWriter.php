<?php

namespace App\Services\Listings;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DraftWriter
{
    public function __construct(private DraftValidator $validator) {}

    public function create(User $actor, int $category, string $token): Listing
    {
        return DB::transaction(function () use ($actor, $category, $token) {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('create', Listing::class);
            if ($existing = Listing::where('user_id', $user->id)->where('creation_token', $token)->first()) {
                return $existing;
            }
            if (Listing::where('user_id', $user->id)->where('status', 'draft')->count() >= config('listings.draft_limit')) {
                throw ValidationException::withMessages(['category_id' => __('listing.draft_limit', ['count' => config('listings.draft_limit')])]);
            }
            // Share the taxonomy lock while validating category structure.
            DB::select('SELECT pg_advisory_xact_lock_shared(782016)');
            $selected = $this->validator->category($category);
            $listing = new Listing(['category_id' => $selected->id]);
            $listing->forceFill(['public_id' => (string) Str::uuid(), 'user_id' => $user->id, 'creation_token' => $token])->save();

            return $listing;
        });
    }

    public function save(User $actor, Listing $draft, string $step, array $input, int $version, bool $complete, bool $advance): Listing
    {
        return DB::transaction(function () use ($actor, $draft, $step, $input, $version, $complete, $advance) {
            $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $listing = Listing::whereKey($draft->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('updateDraft', $listing);
            if ($listing->version !== $version) {
                throw new DraftConflict;
            }
            DB::select('SELECT pg_advisory_xact_lock_shared(782016)');
            if (! $complete) {
                $stored = $this->validator->inputFor($listing, $step);
                if ($step === 'attributes' && is_array($input['attributes'] ?? null)) {
                    $input['attributes'] = array_replace($stored['attributes'], $input['attributes']);
                }
                $input = array_replace($stored, $input);
            }
            $data = $this->validator->validate($listing, $step, $input, $complete);
            if ($step === 'category' && $data['category_id'] !== $listing->category_id) {
                $data['attributes'] = [];
            }
            $listing->fill($data);
            $listing->version++;
            $listing->last_step = $advance ? DraftValidator::STEPS[array_search($step, DraftValidator::STEPS, true) + 1] : $step;
            $listing->save();

            return $listing;
        });
    }
}
