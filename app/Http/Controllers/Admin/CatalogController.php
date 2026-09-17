<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryAttribute;
use App\Services\Catalog\CategoryCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $categories = Category::with('parent:id,name_ro,name_en')->when($query['q'] ?? null, fn ($builder, $q) => $builder->where(fn ($b) => $b->where('name_ro', 'ilike', '%'.$q.'%')->orWhere('name_en', 'ilike', '%'.$q.'%')))->orderBy('position')->orderBy('id')->simplePaginate(30)->withQueryString();

        return view('admin.catalog.index', compact('categories'));
    }

    public function create(Request $request)
    {
        return $this->form($request, new Category);
    }

    public function edit(Request $request, Category $category)
    {
        return $this->form($request, $category);
    }

    private function form(Request $request, Category $category)
    {
        $data = $request->validate(['parent_q' => ['nullable', 'string', 'max:100'], 'parent_id' => ['nullable', 'integer', 'exists:categories,id']]);
        if (! $category->exists && isset($data['parent_id'])) {
            $category->parent_id = $data['parent_id'];
        }
        $parents = Category::where('id', '!=', $category->id ?? 0)->when($data['parent_q'] ?? null, fn ($b, $q) => $b->where(fn ($nested) => $nested->where('name_ro', 'ilike', '%'.$q.'%')->orWhere('name_en', 'ilike', '%'.$q.'%')))->orderBy('name_ro')->limit(50)->get(['id', 'name_ro', 'name_en']);
        if ($category->parent_id && ! $parents->contains('id', $category->parent_id)) {
            $parents->push(Category::findOrFail($category->parent_id));
        }
        $attributes = $category->exists ? CategoryAttribute::where('category_id', $category->id)->orderBy('position')->orderBy('id')->simplePaginate(30) : null;

        return view('admin.catalog.edit', compact('category', 'parents', 'attributes'));
    }

    private function categoryData(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'password' => ['required', 'current_password'],
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', Rule::unique('categories')->ignore($category?->id)],
            'name_ro' => ['required', 'string', 'max:100'], 'name_en' => ['required', 'string', 'max:100'],
            'description_ro' => ['nullable', 'string', 'max:1500'], 'description_en' => ['nullable', 'string', 'max:1500'],
            'icon' => ['required', Rule::in(['grid', 'car', 'parts', 'home', 'laptop', 'garden', 'fish', 'lamp', 'clothes', 'bike'])],
            'is_active' => ['sometimes', 'boolean'], 'position' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);
        unset($data['password']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    public function store(Request $request, CategoryCatalog $catalog)
    {
        $category = $catalog->save(new Category, $this->categoryData($request), $request->user()->id);

        return redirect()->route('admin.categories.edit', $category)->with('status', __('catalog.saved'));
    }

    public function update(Request $request, Category $category, CategoryCatalog $catalog)
    {
        $catalog->save($category, $this->categoryData($request, $category), $request->user()->id);

        return redirect()->route('admin.categories.edit', $category)->with('status', __('catalog.saved'));
    }

    public function createAttribute(Category $category)
    {
        return view('admin.catalog.attribute', ['category' => $category, 'attribute' => new CategoryAttribute]);
    }

    public function editAttribute(Category $category, CategoryAttribute $attribute)
    {
        abort_unless($attribute->category_id === $category->id, 404);

        return view('admin.catalog.attribute', compact('category', 'attribute'));
    }

    public function storeAttribute(Request $request, Category $category, CategoryCatalog $catalog)
    {
        return $this->saveAttribute($request, $category, new CategoryAttribute, $catalog);
    }

    public function updateAttribute(Request $request, Category $category, CategoryAttribute $attribute, CategoryCatalog $catalog)
    {
        abort_unless($attribute->category_id === $category->id, 404);

        return $this->saveAttribute($request, $category, $attribute, $catalog);
    }

    private function saveAttribute(Request $request, Category $category, CategoryAttribute $attribute, CategoryCatalog $catalog)
    {
        $data = $request->validate([
            'password' => ['required', 'current_password'],
            'key' => ['required', 'string', 'max:48', 'regex:/^[a-z][a-z0-9_]*$/D', Rule::unique('category_attributes')->where('category_id', $category->id)->ignore($attribute->id)],
            'label_ro' => ['required', 'string', 'max:100'], 'label_en' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['text', 'number', 'select', 'boolean'])],
            'unit' => ['nullable', 'string', 'max:20'],
            'minimum' => ['nullable', 'numeric', 'between:-999999999999,999999999999'],
            'maximum' => ['nullable', 'numeric', 'between:-999999999999,999999999999'],
            'options_lines' => ['nullable', 'string', 'max:10000'],
            'is_active' => ['sometimes', 'boolean'], 'is_required' => ['sometimes', 'boolean'], 'is_filterable' => ['sometimes', 'boolean'],
            'position' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);
        if ($attribute->exists && ($data['key'] !== $attribute->key || $data['type'] !== $attribute->type)) {
            throw ValidationException::withMessages(['key' => __('catalog.immutable')]);
        }
        $options = [];
        if ($data['type'] === 'select') {
            foreach (preg_split('/\r?\n/', trim($data['options_lines'] ?? '')) as $line) {
                $parts = array_map('trim', explode('|', $line));
                if (count($parts) !== 3 || ! preg_match('/^[a-z0-9_]{1,48}$/D', $parts[0]) || $parts[1] === '' || $parts[2] === '' || mb_strlen($parts[1]) > 100 || mb_strlen($parts[2]) > 100 || isset($options[$parts[0]])) {
                    throw ValidationException::withMessages(['options_lines' => __('catalog.options_invalid')]);
                }
                $options[$parts[0]] = ['value' => $parts[0], 'label_ro' => $parts[1], 'label_en' => $parts[2]];
            }
            if (count($options) > 100) {
                throw ValidationException::withMessages(['options_lines' => __('catalog.options_invalid')]);
            }
        }
        if ($data['type'] === 'number' && isset($data['minimum'],$data['maximum']) && $data['minimum'] > $data['maximum']) {
            throw ValidationException::withMessages(['maximum' => __('catalog.range_invalid')]);
        }
        unset($data['password'],$data['options_lines']);
        $data['options'] = $options ? array_values($options) : null;
        $data['minimum'] = $data['type'] === 'number' ? ($data['minimum'] ?? null) : null;
        $data['maximum'] = $data['type'] === 'number' ? ($data['maximum'] ?? null) : null;
        foreach (['is_active', 'is_required', 'is_filterable'] as $key) {
            $data[$key] = $request->boolean($key);
        }
        DB::transaction(function () use ($request, $category, $attribute, $catalog, $data) {
            DB::select('SELECT pg_advisory_xact_lock(782016)');
            if (CategoryAttribute::where('category_id', $category->id)->where('key', $data['key'])->where('id', '!=', $attribute->id ?? 0)->exists()) {
                throw ValidationException::withMessages(['key' => __('catalog.key_conflict')]);
            }
            $before = $attribute->exists ? $attribute->fresh()->toArray() : null;
            $attribute->fill($data)->forceFill(['category_id' => $category->id])->save();
            $catalog->assertUniqueLineageKeys();
            DB::table('admin_audits')->insert(['actor_id' => $request->user()->id, 'action' => $before ? 'attribute.updated' : 'attribute.created', 'subject_type' => 'category_attribute', 'subject_id' => $attribute->id, 'changes' => json_encode(['before' => $before, 'after' => $attribute->fresh()->toArray()], JSON_THROW_ON_ERROR)]);
        });

        return redirect()->route('admin.categories.edit', $category)->with('status', __('catalog.saved'));
    }
}
