<?php

namespace App\Services\Listings;

use App\Models\Category;
use App\Models\Listing;
use App\Models\SellerProfile;
use App\Services\Catalog\AttributeValidator;
use App\Services\Catalog\CategoryCatalog;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DraftValidator
{
    public const STEPS = ['category', 'photos', 'details', 'attributes', 'price', 'location', 'contact', 'preview'];

    public const PRICING = ['fixed', 'negotiable', 'free', 'exchange', 'contact'];

    public function __construct(private CategoryCatalog $catalog, private AttributeValidator $attributes) {}

    public function category(int $id): Category
    {
        $category = Category::find($id);
        if (! $category || ! $this->catalog->visible($category) || Category::where('parent_id', $category->id)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['category_id' => __('listing.category_unavailable')]);
        }

        return $category;
    }

    public function validate(Listing $listing, string $step, array $input, bool $complete): array
    {
        $required = $complete ? 'required' : 'nullable';
        $labels = [];
        foreach (['category_id', 'title', 'description', 'pricing_mode', 'price', 'currency', 'country_code', 'county_id', 'locality', 'contact_method'] as $key) {
            $labels[$key] = __('listing.'.$key);
        }
        $rules = match ($step) {
            'category' => ['category_id' => ['required', 'integer']],
            'photos' => [],
            'details' => ['title' => [$required, 'string', 'max:140', ...($complete ? ['min:8'] : [])], 'description' => [$required, 'string', 'max:5000', ...($complete ? ['min:30'] : [])]],
            'attributes' => ['attributes' => ['sometimes', 'array', 'max:100']],
            'price' => [
                'pricing_mode' => ['required', Rule::in(self::PRICING)],
                'currency' => ['required', Rule::in(config('listings.currencies'))],
                'price' => ['exclude_if:pricing_mode,free,exchange,contact', 'nullable', 'string', 'regex:/^\d{1,9}(?:[.,]\d{1,2})?$/'],
            ],
            'location' => [
                'country_code' => ['required', Rule::in(config('listings.countries'))],
                'county_id' => [$required, 'integer', Rule::exists('counties', 'id')->where('country_code', is_string($input['country_code'] ?? null) ? $input['country_code'] : '')],
                'locality' => [$required, 'string', 'max:100', 'regex:/^[\pL\pM\d .\x{2019}\x{0027}()\-]+$/u'],
            ],
            'contact' => ['contact_method' => [$required, Rule::in(['email', 'phone'])]],
            default => throw new \InvalidArgumentException('Unknown writable draft step.'),
        };
        $data = Validator::make($input, $rules, [], $labels)->validate();
        if ($step === 'category') {
            $data['category_id'] = $this->category((int) $data['category_id'])->id;
        } elseif ($step === 'photos') {
            if ($complete && $listing->photos()->where('status', '!=', 'ready')->exists()) {
                throw ValidationException::withMessages(['photos' => __('photo.not_ready')]);
            }
        } elseif ($step === 'details') {
            $data = ['title' => $data['title'] ?? '', 'description' => $data['description'] ?? ''];
        } elseif ($step === 'attributes') {
            try {
                $data = ['attributes' => $this->attributes->validate($listing->category, $data['attributes'] ?? [], $complete)];
            } catch (ValidationException $exception) {
                $errors = [];
                foreach ($exception->errors() as $key => $messages) {
                    $errors['attributes.'.$key] = $messages;
                }
                throw ValidationException::withMessages($errors);
            }
        } elseif ($step === 'price') {
            $priced = in_array($data['pricing_mode'], ['fixed', 'negotiable'], true);
            $price = $data['price'] ?? null;
            if ($complete && $priced && $price === null) {
                throw ValidationException::withMessages(['price' => __('listing.price_required')]);
            }
            $minor = null;
            if ($priced && $price !== null) {
                $parts = explode('.', str_replace(',', '.', $price));
                $minor = ((int) $parts[0]) * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
                if ($minor === 0) {
                    throw ValidationException::withMessages(['price' => __('listing.price_positive')]);
                }
            }
            unset($data['price']);
            $data['price_minor'] = $data['pricing_mode'] === 'free' ? 0 : $minor;
        } elseif ($step === 'location') {
            $data['county_id'] = isset($data['county_id']) ? (int) $data['county_id'] : null;
            $data['locality'] = $data['locality'] ?? null;
        } elseif ($step === 'contact') {
            $data['contact_method'] = $data['contact_method'] ?? null;
            if ($data['contact_method'] === 'phone' && ! SellerProfile::where('user_id', $listing->user_id)->value('phone')) {
                throw ValidationException::withMessages(['contact_method' => __('listing.phone_missing')]);
            }
        }

        return $data;
    }

    public function inputFor(Listing $listing, string $step): array
    {
        return match ($step) {
            'category' => $listing->only('category_id'),
            'details' => $listing->only('title', 'description'),
            'attributes' => $listing->only('attributes'),
            'price' => [...$listing->only('pricing_mode', 'currency'), 'price' => $listing->price_minor === null ? null : $listing->priceInput()],
            'location' => $listing->only('country_code', 'county_id', 'locality'),
            'contact' => $listing->only('contact_method'),
            default => [],
        };
    }

    public function issues(Listing $listing): array
    {
        $issues = [];
        foreach (array_diff(self::STEPS, ['preview']) as $step) {
            try {
                $this->validate($listing, $step, $this->inputFor($listing, $step), true);
            } catch (ValidationException $exception) {
                $issues[$step] = $exception->errors();
            }
        }

        return $issues;
    }
}
