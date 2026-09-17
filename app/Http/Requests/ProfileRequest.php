<?php

namespace App\Http\Requests;

use App\Models\SellerProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasVerifiedEmail() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $values = [];
        if (is_string($this->input('username'))) {
            $values['username'] = mb_strtolower(trim($this->input('username')));
        }
        if (is_string($this->input('phone'))) {
            $phone = preg_replace('/[ ()-]/', '', $this->input('phone'));
            if (preg_match('/^0[0-9]{9}$/D', $phone)) {
                $phone = '+40'.substr($phone, 1);
            }
            $values['phone'] = $phone;
        }
        $this->merge($values);
    }

    public function rules(): array
    {
        $profile = SellerProfile::where('user_id', $this->user()->id)->first();

        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'username' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[a-z0-9][a-z0-9_]*$/D', Rule::notIn(['admin', 'administrator', 'support', 'aproape', 'system', 'api']), Rule::unique('seller_profiles')->ignore($profile?->id)],
            'account_type' => ['required', Rule::in($this->user()->is_admin ? ['individual'] : ['individual', 'business'])],
            'description' => ['nullable', 'string', 'max:1500'],
            'location_label' => ['nullable', 'string', 'max:100'],
            'business_name' => ['exclude_unless:account_type,business', 'required_if:account_type,business', 'string', 'max:160'],
            'tax_id' => ['exclude_unless:account_type,business', 'nullable', 'string', 'regex:/^(RO)?[0-9]{2,10}$/D'],
            'show_tax_id' => ['sometimes', 'boolean'],
            'phone' => ['nullable', 'required_if:contact_method,phone', 'string', 'regex:/^\\+[1-9][0-9]{7,14}$/D'],
            'contact_method' => ['required', Rule::in(['email', 'phone'])],
            'is_public' => ['sometimes', 'boolean'],
            'avatar' => ['nullable', 'file', 'max:5120', 'mimetypes:image/jpeg,image/png,image/webp'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ];
    }
}
