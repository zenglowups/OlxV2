<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\Catalog\CategoryCatalog;

class CatalogController extends Controller
{
    public function home()
    {
        return view('home', ['categories' => Category::whereNull('parent_id')->where('is_active', true)->orderBy('position')->orderBy('id')->limit(12)->get(['id', 'slug', 'name_ro', 'name_en', 'icon'])]);
    }

    public function index()
    {
        return view('catalog.index', ['category' => null, 'ancestors' => collect(), 'categories' => Category::whereNull('parent_id')->where('is_active', true)->orderBy('position')->orderBy('id')->simplePaginate(24, ['id', 'slug', 'name_ro', 'name_en', 'icon'])]);
    }

    public function show(Category $category, CategoryCatalog $catalog)
    {
        abort_unless($catalog->visible($category), 404);

        return view('catalog.index', ['category' => $category, 'ancestors' => $catalog->ancestors($category), 'categories' => Category::where('parent_id', $category->id)->where('is_active', true)->orderBy('position')->orderBy('id')->simplePaginate(24, ['id', 'slug', 'name_ro', 'name_en', 'icon'])]);
    }
}
