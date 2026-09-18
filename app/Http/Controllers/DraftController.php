<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\County;
use App\Models\Listing;
use App\Services\Catalog\CategoryCatalog;
use App\Services\Listings\DraftConflict;
use App\Services\Listings\DraftValidator;
use App\Services\Listings\DraftWriter;
use App\Services\Listings\ListingLifecycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DraftController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('create', Listing::class);
        $drafts = Listing::where('user_id', $request->user()->id)->where('status', 'draft')
            ->select(['id', 'public_id', 'category_id', 'title', 'pricing_mode', 'price_minor', 'currency', 'last_step', 'updated_at'])
            ->with(['category:id,name_ro,name_en,icon', 'photos' => fn ($query) => $query->select(['id', 'public_id', 'listing_id', 'position', 'status', 'variants'])->where('status', 'ready')->limit(1)])->orderByDesc('updated_at')->orderByDesc('id')->paginate(12);

        return view('listings.drafts', compact('drafts'));
    }

    public function create(Request $request, CategoryCatalog $catalog)
    {
        Gate::authorize('create', Listing::class);
        $query = $request->validate(['category' => ['nullable', 'string', 'max:100'], 'draft' => ['nullable', 'uuid']]);
        $draft = isset($query['draft']) ? Listing::where('public_id', $query['draft'])->firstOrFail() : null;
        if ($draft) {
            Gate::authorize('updateDraft', $draft);
        }
        $category = isset($query['category']) ? Category::where('slug', $query['category'])->firstOrFail() : null;
        if ($category) {
            abort_unless($catalog->visible($category), 404);
        }
        $categories = Category::where('parent_id', $category?->id)->where('is_active', true)
            ->orderBy('position')->orderBy('id')->paginate(24, ['id', 'slug', 'name_ro', 'name_en', 'icon']);

        return view('listings.choose-category', [
            'draft' => $draft, 'category' => $category, 'categories' => $categories->withQueryString(),
            'ancestors' => $category ? $catalog->ancestors($category) : collect(),
        ]);
    }

    public function store(Request $request, DraftWriter $writer)
    {
        Gate::authorize('create', Listing::class);
        $data = $request->validate(['category_id' => ['required', 'integer'], 'creation_token' => ['required', 'uuid']]);
        $listing = $writer->create($request->user(), (int) $data['category_id'], $data['creation_token']);

        return redirect()->route('drafts.edit', ['listing' => $listing, 'step' => $listing->last_step]);
    }

    public function edit(Listing $listing, string $step, CategoryCatalog $catalog)
    {
        Gate::authorize('updateDraft', $listing);
        if ($step === 'category') {
            return redirect()->route('drafts.create', ['draft' => $listing->public_id, 'category' => $listing->category->slug]);
        }
        if ($step === 'preview') {
            return redirect()->route('drafts.preview', $listing);
        }

        if ($step === 'photos') {
            return view('listings.photos', ['listing' => $listing, 'step' => 'photos', 'steps' => DraftValidator::STEPS, 'photos' => $listing->photos()->get()]);
        }

        return view('listings.wizard', [
            'listing' => $listing, 'step' => $step, 'steps' => DraftValidator::STEPS,
            'definitions' => $step === 'attributes' ? $catalog->attributes($listing->category) : collect(),
            'counties' => $step === 'location' ? County::where('country_code', 'RO')->orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }

    public function update(Request $request, Listing $listing, string $step, DraftWriter $writer)
    {
        Gate::authorize('updateDraft', $listing);
        $data = $request->validate(['version' => ['required', 'integer', 'min:1', 'max:2147483646'], 'intent' => ['sometimes', Rule::in(['next', 'exit'])]]);
        $partial = $request->isMethod('PATCH') || ($data['intent'] ?? 'next') === 'exit';
        try {
            $saved = $writer->save($request->user(), $listing, $step, $request->all(), (int) $data['version'], ! $partial, ! $partial);
        } catch (DraftConflict) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __('listing.conflict'), 'conflict' => true], 409);
            }

            return response()->view('listings.conflict', ['listing' => $listing, 'step' => $step, 'submitted' => $request->only(['title', 'description', 'attributes', 'pricing_mode', 'price', 'currency', 'country_code', 'county_id', 'locality', 'contact_method', 'category_id'])], 409);
        }
        if ($request->isMethod('PATCH')) {
            return response()->json(['version' => $saved->version, 'message' => __('listing.saved')]);
        }
        if ($partial) {
            return redirect()->route('drafts.index')->with('status', __('listing.saved'));
        }

        return redirect()->route($saved->last_step === 'preview' ? 'drafts.preview' : 'drafts.edit', $saved->last_step === 'preview' ? ['listing' => $saved] : ['listing' => $saved, 'step' => $saved->last_step]);
    }

    public function preview(Listing $listing, DraftValidator $validator, CategoryCatalog $catalog, ListingLifecycle $lifecycle)
    {
        Gate::authorize('updateDraft', $listing);
        $listing->load('category', 'county');

        return view('listings.preview', ['listing' => $listing, 'steps' => DraftValidator::STEPS, 'step' => 'preview', 'issues' => $validator->issues($listing), 'profileReady' => $lifecycle->profileReady($listing), 'photos' => $listing->photos()->where('status', 'ready')->get(), 'definitions' => $catalog->attributes($listing->category)]);
    }
}
