<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\ListingPhoto;
use App\Services\Listings\DraftConflict;
use App\Services\Media\ListingPhotos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ListingPhotoController extends Controller
{
    public function store(Request $request, Listing $listing, ListingPhotos $photos)
    {
        Gate::authorize('updateDraft', $listing);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1'], 'photos' => ['required', 'array', 'min:1', 'max:8'], 'photos.*' => ['required', 'file', 'max:10240']]);
        try {
            $failed = $photos->upload($request->user(), $listing, $data['photos'], (int) $data['version']);
        } catch (DraftConflict) {
            return $this->conflict($listing);
        }
        $response = redirect()->route('drafts.edit', ['listing' => $listing, 'step' => 'photos']);

        return $failed ? $response->withErrors(['photos' => __('photo.upload_failed')]) : $response->with('status', __('photo.uploaded'));
    }

    public function update(Request $request, Listing $listing, ListingPhoto $photo, ListingPhotos $photos)
    {
        Gate::authorize('updateDraft', $listing);
        abort_unless($photo->listing_id === $listing->id, 404);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1'], 'action' => ['required', Rule::in(['primary', 'left', 'right'])]]);
        try {
            $photos->arrange($request->user(), $listing, $photo, $data['action'], (int) $data['version']);
        } catch (DraftConflict) {
            return $this->conflict($listing);
        }

        return redirect()->route('drafts.edit', ['listing' => $listing, 'step' => 'photos'])->with('status', __('photo.saved'));
    }

    public function destroy(Request $request, Listing $listing, ListingPhoto $photo, ListingPhotos $photos)
    {
        Gate::authorize('updateDraft', $listing);
        abort_unless($photo->listing_id === $listing->id, 404);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1']]);
        try {
            $photos->remove($request->user(), $listing, $photo, (int) $data['version']);
        } catch (DraftConflict) {
            return $this->conflict($listing);
        }

        return redirect()->route('drafts.edit', ['listing' => $listing, 'step' => 'photos'])->with('status', __('photo.removed'));
    }

    public function status(Listing $listing)
    {
        Gate::authorize('updateDraft', $listing);

        return response()->json(['pending' => ListingPhoto::where('listing_id', $listing->id)->whereIn('status', ['uploading', 'queued'])->exists()]);
    }

    public function image(Request $request, ListingPhoto $photo, string $variant)
    {
        if (! ($request->user() && Gate::allows('manage', $photo->listing))) {
            abort_unless(Listing::publiclyVisible()->whereKey($photo->listing_id)->exists(), 404);
        }
        abort_unless($photo->status === 'ready' && isset($photo->variants[$variant]), 404);

        return Storage::disk('s3')->response($photo->variantPath($variant), 'listing.webp', [
            'Content-Type' => 'image/webp', 'Cache-Control' => 'no-store, private', 'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function conflict(Listing $listing)
    {
        return response()->view('listings.conflict', ['listing' => $listing, 'step' => 'photos', 'submitted' => []], 409);
    }
}
