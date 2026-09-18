<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Hidden(['storage_prefix', 'source_hash'])]
class ListingPhoto extends Model
{
    public const VARIANTS = ['thumb' => 320, 'card' => 640, 'full' => 1280];

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['variants' => 'array', 'position' => 'integer', 'width' => 'integer', 'height' => 'integer', 'source_bytes' => 'integer', 'dispatched_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function sourcePath(): string
    {
        return $this->storage_prefix.'/source.bin';
    }

    public function variantPath(string $variant): string
    {
        if (! array_key_exists($variant, self::VARIANTS)) {
            throw new \InvalidArgumentException('Unknown image variant.');
        }

        return $this->storage_prefix.'/'.$variant.'.webp';
    }

    public function paths(): array
    {
        return [$this->sourcePath(), ...array_map(fn ($variant) => $this->variantPath($variant), array_keys(self::VARIANTS))];
    }

    public function srcset(): string
    {
        $sources = [];
        foreach (array_keys(self::VARIANTS) as $variant) {
            if (isset($this->variants[$variant])) {
                $sources[$this->variants[$variant]['width']] = $this->imageUrl($variant);
            }
        }
        ksort($sources);
        $entries = [];
        foreach ($sources as $width => $url) {
            $entries[] = $url.' '.$width.'w';
        }

        return implode(', ', $entries);
    }

    public function imageUrl(string $variant): string
    {
        return route('listing-image', ['photo' => $this->public_id, 'variant' => $variant]);
    }
}
