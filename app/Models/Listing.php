<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['category_id', 'title', 'description', 'attributes', 'pricing_mode', 'price_minor', 'currency', 'country_code', 'county_id', 'locality', 'contact_method'])]
#[Hidden(['creation_token'])]
class Listing extends Model
{
    protected $attributes = ['title' => '', 'description' => '', 'attributes' => '{}', 'pricing_mode' => 'fixed', 'currency' => 'RON', 'country_code' => 'RO', 'status' => 'draft', 'moderation_state' => 'unreviewed', 'version' => 1, 'last_step' => 'photos'];

    protected function casts(): array
    {
        return ['attributes' => 'array', 'price_minor' => 'integer', 'version' => 'integer', 'published_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ListingPhoto::class)->orderBy('position')->orderBy('id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function county(): BelongsTo
    {
        return $this->belongsTo(County::class);
    }

    public function priceInput(): string
    {
        return $this->price_minor === null ? '' : intdiv($this->price_minor, 100).'.'.str_pad((string) ($this->price_minor % 100), 2, '0', STR_PAD_LEFT);
    }

    public function priceLabel(): string
    {
        if (in_array($this->pricing_mode, ['free', 'exchange', 'contact'], true)) {
            return __('listing.price_'.$this->pricing_mode);
        }

        return $this->price_minor === null ? __('listing.not_set') : number_format($this->price_minor / 100, $this->price_minor % 100 ? 2 : 0, app()->getLocale() === 'ro' ? ',' : '.', app()->getLocale() === 'ro' ? '.' : ',').' '.$this->currency;
    }
}
