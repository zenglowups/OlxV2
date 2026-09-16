@extends('layouts.app')
@section('title', __('auth.login'))
@section('content')
<section class="auth-shell container">
    <div class="auth-intro"><p class="eyebrow">{{ config('app.name') }}</p><h1>{{ __('auth.welcome') }}</h1><p>{{ __('auth.login_intro') }}</p></div>
    <div class="form-card">
        <x-form-feedback />
        <form method="POST" action="{{ route('login') }}">@csrf
            <x-form-field name="email" :label="__('auth.email')" type="email" autocomplete="email" inputmode="email" required maxlength="254" />
            <x-form-field name="password" :label="__('auth.password')" type="password" autocomplete="current-password" required maxlength="128" />
            <label class="checkbox-field"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> {{ __('auth.remember') }}</label>
            <button class="button" type="submit">{{ __('auth.login') }}</button>
        </form>
        <a class="text-link" href="{{ route('password.request') }}">{{ __('auth.forgot') }}</a>
        <x-oauth-buttons />
        <p>{{ __('auth.no_account') }} <a href="{{ route('register') }}">{{ __('auth.register') }}</a></p>
    </div>
</section>
@endsection
