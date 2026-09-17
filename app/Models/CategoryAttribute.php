<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryAttribute extends Model
{
    protected $fillable = ['category_id', 'key', 'label_ro', 'label_en', 'type', 'unit', 'options', 'minimum', 'maximum', 'is_required', 'is_filterable', 'is_active', 'position'];

    protected $attributes = ['is_active' => true, 'is_required' => false, 'is_filterable' => false, 'position' => 0];

    protected function casts(): array
    {
        return ['options' => 'array', 'is_active' => 'boolean', 'is_required' => 'boolean', 'is_filterable' => 'boolean', 'minimum' => 'float', 'maximum' => 'float'];
    }

    public function label(): string
    {
        return $this->{'label_'.app()->getLocale()};
    }
}
