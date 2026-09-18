<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Services\Catalog\CategoryCatalog;
use App\Services\Listings\DraftConflict;
use App\Services\Listings\ListingFeed;
use App\Services\Listings\ListingLifecycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ListingController extends Controller
{
    public function index(ListingFeed $feed)
    {
        return view('listings.index', ['listings' => $feed->query()->simplePaginate(12)]);
    }

    public function show(Listing $listing, CategoryCatalog $catalog)
    {
        abort_unless(Listing::publiclyVisible()->whereKey($listing->id)->exists(), 404);
        $listing->load('category', 'county', 'photos', 'user:id,name,account_type,email_verified_at,created_at', 'user.profile:id,user_id,username,business_name,avatar_id');

        return response()->view('listings.show', ['listing' => $listing, 'photos' => $listing->photos->where('status', 'ready'), 'definitions' => $catalog->attributes($listing->category), 'ancestors' => $catalog->ancestors($listing->category)])->header('Cache-Control', 'no-store, private');
    }

    public function manage(Request $request, ListingLifecycle $lifecycle)
    {
        Gate::authorize('create', Listing::class);
        $data = $request->validate(['status' => ['sometimes', Rule::in(Listing::STATES)]]);
        $status = $data['status'] ?? null;
        $base = Listing::where('user_id', $request->user()->id);
        $counts = (clone $base)->selectRaw("CASE WHEN status IN ('active','reserved') AND expires_at <= ? THEN 'expired' ELSE status END AS state, count(*) AS total", [now()])->groupBy('state')->pluck('total', 'state');
        $listings = (clone $base);
        if ($status === 'expired') {
            $listings->where(fn ($q) => $q->where('status', 'expired')->orWhere(fn ($q) => $q->whereIn('status', ['active', 'reserved'])->where('expires_at', '<=', now())));
        } elseif ($status) {
            $listings->where('status', $status);
            if (in_array($status, ['active', 'reserved'], true)) {
                $listings->where('expires_at', '>', now());
            }
        }
        $listings = $listings->with(['category:id,name_ro,name_en,icon', 'photos' => fn ($photos) => $photos->where('status', 'ready')->limit(1)->select('id', 'public_id', 'listing_id', 'position', 'status', 'variants')])
            ->orderByDesc('updated_at')->orderByDesc('id')->simplePaginate(12, [...ListingFeed::COLUMNS, 'last_step', 'version', 'moderation_state'])->withQueryString();

        $publicIds = Listing::publiclyVisible()->whereIn('id', $listings->pluck('id'))->pluck('id');

        return view('listings.manage', compact('listings', 'counts', 'status', 'lifecycle', 'publicIds'));
    }

    public function confirm(Listing $listing, string $action, ListingLifecycle $lifecycle)
    {
        Gate::authorize('manage', $listing);
        abort_unless(in_array($action, $lifecycle->actions($listing), true), 404);

        return view('listings.confirm', compact('listing', 'action'));
    }

    public function publish(Request $request, Listing $listing, ListingLifecycle $lifecycle)
    {
        Gate::authorize('updateDraft', $listing);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        try {
            $saved = $lifecycle->publish($request->user(), $listing, (int) $data['version']);
        } catch (DraftConflict) {
            return $this->conflict($listing);
        }

        return redirect()->route('listings.show', $saved)->with('status', __('market.published'));
    }

    public function transition(Request $request, Listing $listing, string $action, ListingLifecycle $lifecycle)
    {
        Gate::authorize('manage', $listing);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        try {
            $saved = $lifecycle->transition($request->user(), $listing, $action, (int) $data['version']);
        } catch (DraftConflict) {
            return $this->conflict($listing);
        }

        return $action === 'edit' ? redirect()->route('drafts.edit', ['listing' => $saved, 'step' => 'details'])->with('status', __('market.edit_started'))
            : redirect()->route('listings.manage')->with('status', __('market.saved'));
    }

    private function conflict(Listing $listing)
    {
        return response()->view('listings.lifecycle-conflict', ['listing' => $listing], 409);
    }
}
