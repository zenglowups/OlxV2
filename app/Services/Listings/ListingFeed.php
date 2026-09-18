<?php

namespace App\Services\Listings;

use App\Models\Category;
use App\Models\Listing;
use Illuminate\Database\Eloquent\Builder;

class ListingFeed
{
    public const COLUMNS = ['id', 'public_id', 'user_id', 'category_id', 'title', 'pricing_mode', 'price_minor', 'currency', 'locality', 'county_id', 'status', 'published_at', 'expires_at', 'updated_at'];

    public function query(?Category $category = null): Builder
    {
        $query = Listing::publiclyVisible()->select(self::COLUMNS)->with([
            'category:id,slug,name_ro,name_en,icon', 'county:id,name',
            'photos' => fn ($photos) => $photos->where('status', 'ready')->limit(1)->select('id', 'public_id', 'listing_id', 'position', 'status', 'variants'),
        ])->orderByDesc('published_at')->orderByDesc('id');
        if ($category) {
            $query->whereRaw('category_id IN (WITH RECURSIVE branch AS (SELECT id FROM categories WHERE id = ? UNION ALL SELECT c.id FROM categories c JOIN branch b ON c.parent_id = b.id) SELECT id FROM branch)', [$category->id]);
        }

        return $query;
    }
}
