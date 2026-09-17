<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerProfile extends Model
{
    protected $fillable = ['user_id', 'username', 'description', 'location_label', 'business_name', 'tax_id', 'show_tax_id', 'phone', 'contact_method', 'is_public'];

    protected $hidden = ['phone', 'tax_id', 'avatar_path'];

    protected $attributes = ['is_public' => false, 'show_tax_id' => false, 'contact_method' => 'email'];

    protected function casts(): array
    {
        return ['phone' => 'encrypted', 'tax_id' => 'encrypted', 'is_public' => 'boolean', 'show_tax_id' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function publiclyVisible(): bool
    {
        return $this->is_public && $this->user?->hasVerifiedEmail() && $this->user->canAuthenticate();
    }

    public function getRouteKeyName(): string
    {
        return 'username';
    }
}
