@extends('layouts.app')
@section('title', __('auth.reset_title'))
@section('content')
<section class="auth-shell container">
    <div class="auth-intro"><h1>{{ __('auth.reset_title') }}</h1></div>
    <div class="form-card">
        <x-form-feedback />
        <form method="POST" action="{{ route('password.update') }}">@csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <x-form-field name="email" :label="__('auth.email')" type="email" :value="$email" autocomplete="email" required maxlength="254" />
            <x-form-field name="password" :label="__('auth.password')" type="password" autocomplete="new-password" :hint="__('auth.password_hint')" required minlength="12" maxlength="128" />
            <x-form-field name="password_confirmation" :label="__('auth.password_confirmation')" type="password" autocomplete="new-password" required minlength="12" maxlength="128" />
            <button class="button" type="submit">{{ __('auth.reset_action') }}</button>
        </form>
        <a class="text-link" href="{{ route('password.request') }}">{{ __('auth.send_reset') }}</a>
    </div>
</section>
@endsection
