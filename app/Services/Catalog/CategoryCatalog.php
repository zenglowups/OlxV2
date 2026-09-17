<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\CategoryAttribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CategoryCatalog
{
    public function ancestors(Category $category): Collection
    {
        return collect(Category::fromQuery('WITH RECURSIVE lineage AS (
            SELECT categories.*, 0 AS depth FROM categories WHERE id = ?
            UNION ALL SELECT c.*, l.depth + 1 FROM categories c JOIN lineage l ON l.parent_id = c.id
        ) SELECT * FROM lineage ORDER BY depth DESC', [$category->id]));
    }

    public function visible(Category $category): bool
    {
        return $this->ancestors($category)->every(fn ($node) => $node->is_active);
    }

    public function attributes(Category $category): Collection
    {
        $ids = $this->ancestors($category)->pluck('id');
        $attributes = CategoryAttribute::whereIn('category_id', $ids)->where('is_active', true)->orderBy('position')->orderBy('id')->get();

        // Keys are unique across a lineage, enforced when definitions or parents change.
        return $attributes->values();
    }

    public function save(Category $category, array $data, int $actor): Category
    {
        return DB::transaction(function () use ($category, $data, $actor) {
            // Serialize structural edits so two concurrent moves cannot create a cycle.
            DB::select('SELECT pg_advisory_xact_lock(782016)');
            if (Category::where('slug', $data['slug'])->where('id', '!=', $category->id ?? 0)->exists()) {
                throw ValidationException::withMessages(['slug' => __('validation.unique', ['attribute' => __('catalog.slug')])]);
            }
            $parent = isset($data['parent_id']) ? Category::findOrFail($data['parent_id']) : null;
            if ($parent && $category->exists && $this->ancestors($parent)->contains('id', $category->id)) {
                throw ValidationException::withMessages(['parent_id' => __('catalog.cycle')]);
            }
            $before = $category->exists ? $category->fresh()->only($category->getFillable()) : null;
            $category->fill($data)->save();
            $this->assertUniqueLineageKeys();
            DB::table('admin_audits')->insert(['actor_id' => $actor, 'action' => $before ? 'category.updated' : 'category.created', 'subject_type' => 'category', 'subject_id' => $category->id, 'changes' => json_encode(['before' => $before, 'after' => $category->only($category->getFillable())], JSON_THROW_ON_ERROR)]);

            return $category;
        });
    }

    public function assertUniqueLineageKeys(): void
    {
        $conflicts = DB::select('WITH RECURSIVE paths AS (
            SELECT id AS descendant, id AS ancestor, parent_id FROM categories
            UNION ALL SELECT p.descendant, c.id, c.parent_id FROM paths p JOIN categories c ON c.id=p.parent_id
        ) SELECT p.descendant, a.key FROM paths p JOIN category_attributes a ON a.category_id=p.ancestor
          WHERE a.is_active GROUP BY p.descendant,a.key HAVING count(*)>1 LIMIT 1');
        if ($conflicts) {
            throw ValidationException::withMessages(['key' => __('catalog.key_conflict')]);
        }
    }
}
