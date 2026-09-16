<?php

namespace App\Http\Requests\Auth;

use App\Support\Email;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => Email::normalize($this->input('email'))]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'string', 'email', 'max:254', 'unique:users,email'],
            'password' => ['required', 'string', 'max:128', 'confirmed', Password::min(12)],
            'account_type' => ['required', 'in:individual,business'],
        ];
    }
}
