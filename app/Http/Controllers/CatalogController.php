<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\Catalog\CategoryCatalog;
use App\Services\Listings\ListingFeed;

class CatalogController extends Controller
{
    public function home(ListingFeed $feed)
    {
        return view('home', ['latestListings' => $feed->query()->limit(4)->get(), 'categories' => Category::whereNull('parent_id')->where('is_active', true)->orderBy('position')->orderBy('id')->limit(12)->get(['id', 'slug', 'name_ro', 'name_en', 'icon'])]);
    }

    public function index()
    {
        return view('catalog.index', ['category' => null, 'ancestors' => collect(), 'categories' => Category::whereNull('parent_id')->where('is_active', true)->orderBy('position')->orderBy('id')->simplePaginate(24, ['id', 'slug', 'name_ro', 'name_en', 'icon'])]);
    }

    public function show(Category $category, CategoryCatalog $catalog, ListingFeed $feed)
    {
        abort_unless($catalog->visible($category), 404);

        return view('catalog.index', ['listings' => $feed->query($category)->simplePaginate(12, ['*'], 'listings_page'), 'category' => $category, 'ancestors' => $catalog->ancestors($category), 'categories' => Category::where('parent_id', $category->id)->where('is_active', true)->orderBy('position')->orderBy('id')->simplePaginate(24, ['id', 'slug', 'name_ro', 'name_en', 'icon'])]);
    }
}
