<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['category_id', 'title', 'description', 'attributes', 'pricing_mode', 'price_minor', 'currency', 'country_code', 'county_id', 'locality', 'contact_method'])]
#[Hidden(['creation_token'])]
class Listing extends Model
{
    public const STATES = ['draft', 'active', 'reserved', 'sold', 'expired', 'suspended', 'removed'];

    protected $attributes = ['title' => '', 'description' => '', 'attributes' => '{}', 'pricing_mode' => 'fixed', 'currency' => 'RON', 'country_code' => 'RO', 'status' => 'draft', 'moderation_state' => 'unreviewed', 'version' => 1, 'last_step' => 'photos'];

    protected function casts(): array
    {
        return ['attributes' => 'array', 'price_minor' => 'integer', 'version' => 'integer', 'published_at' => 'datetime', 'expires_at' => 'datetime', 'renewed_at' => 'datetime', 'removed_at' => 'datetime'];
    }

    public function effectiveStatus(): string
    {
        return in_array($this->status, ['active', 'reserved'], true) && $this->expires_at?->lte(now()) ? 'expired' : $this->status;
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->whereIn('status', ['active', 'reserved'])->whereIn('moderation_state', ['unreviewed', 'reviewed'])
            ->whereNotNull('published_at')->where('expires_at', '>', now())
            ->whereHas('user', fn ($users) => $users->whereNotNull('email_verified_at')->where('is_admin', false)
                ->where(fn ($users) => $users->where('status', 'active')->orWhere(fn ($users) => $users->where('status', 'suspended')->where('suspended_until', '<', now()))))
            ->whereHas('user.profile', fn ($profiles) => $profiles->where('is_public', true))
            ->whereRaw('category_id IN (WITH RECURSIVE visible AS (SELECT id FROM categories WHERE parent_id IS NULL AND is_active UNION ALL SELECT c.id FROM categories c JOIN visible v ON c.parent_id = v.id WHERE c.is_active) SELECT id FROM visible)');
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
