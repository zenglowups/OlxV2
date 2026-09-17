<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    protected $fillable = ['parent_id', 'slug', 'name_ro', 'name_en', 'description_ro', 'description_en', 'icon', 'is_active', 'position'];

    protected $attributes = ['is_active' => true, 'position' => 0, 'icon' => 'grid'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'position' => 'integer'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function label(): string
    {
        return $this->{'name_'.app()->getLocale()};
    }

    public function description(): ?string
    {
        return $this->{'description_'.app()->getLocale()};
    }
}
