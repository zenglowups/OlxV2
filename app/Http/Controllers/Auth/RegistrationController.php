<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\Auth\AuthenticatedSession;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class RegistrationController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, AuthenticatedSession $session)
    {
        try {
            $user = User::create($request->validated() + ['locale' => app()->getLocale()])->refresh();
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages(['email' => __('validation.unique', ['attribute' => __('auth.email')])]);
        }
        event(new Registered($user));
        $session->start($request, $user);

        return redirect()->route('verification.notice');
    }
}
