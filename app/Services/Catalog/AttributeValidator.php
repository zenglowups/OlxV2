<?php

namespace App\Services\Catalog;

use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AttributeValidator
{
    public function __construct(private CategoryCatalog $catalog) {}

    public function validate(Category $category, array $values, bool $complete = true): array
    {
        $definitions = $this->catalog->attributes($category);
        $rules = [];
        $labels = [];
        foreach ($definitions as $definition) {
            $rule = [$complete && $definition->is_required ? 'required' : 'nullable'];
            if ($definition->type === 'number') {
                $rule[] = 'numeric';
                if ($definition->minimum !== null) {
                    $rule[] = 'min:'.$definition->minimum;
                }
                if ($definition->maximum !== null) {
                    $rule[] = 'max:'.$definition->maximum;
                }
            } elseif ($definition->type === 'select') {
                $rule[] = 'string';
                $rule[] = Rule::in(array_column($definition->options ?? [], 'value'));
            } elseif ($definition->type === 'boolean') {
                $rule[] = 'boolean';
            } else {
                $rule[] = 'string';
                $rule[] = 'max:200';
            }
            $rules[$definition->key] = $rule;
            $labels[$definition->key] = $definition->label();
        }
        $validated = Validator::make($values, $rules, [], $labels)->validate();
        $result = [];
        foreach ($definitions as $definition) {
            $value = $validated[$definition->key] ?? null;
            if ($value === null) {
                continue;
            }
            $result[$definition->key] = match ($definition->type) {
                'number' => (float) $value, 'boolean' => (bool) $value, default => $value
            };
        }

        return $result;
    }
}
