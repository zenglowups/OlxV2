@extends('layouts.app')
@section('title', __('auth.register'))
@section('content')
<section class="auth-shell container">
    <div class="auth-intro"><p class="eyebrow">{{ config('app.name') }}</p><h1>{{ __('auth.register_intro') }}</h1><p>{{ __('auth.register_body') }}</p></div>
    <div class="form-card">
        <x-form-feedback />
        <form method="POST" action="{{ route('register') }}">@csrf
            <x-form-field name="name" :label="__('auth.name')" autocomplete="nickname" required minlength="2" maxlength="80" />
            <x-form-field name="email" :label="__('auth.email')" type="email" autocomplete="email" inputmode="email" required maxlength="254" />
            <div class="form-field"><label for="account_type">{{ __('auth.account_type') }}</label>
                <select name="account_type" id="account_type" required aria-describedby="type-help">
                    <option value="individual" @selected(old('account_type') === 'individual')>{{ __('auth.individual') }}</option>
                    <option value="business" @selected(old('account_type') === 'business')>{{ __('auth.business') }}</option>
                </select><p class="field-hint" id="type-help">{{ __('auth.business_hint') }}</p>
            </div>
            <x-form-field name="password" :label="__('auth.password')" type="password" autocomplete="new-password" :hint="__('auth.password_hint')" required minlength="12" maxlength="128" />
            <x-form-field name="password_confirmation" :label="__('auth.password_confirmation')" type="password" autocomplete="new-password" required minlength="12" maxlength="128" />
            <button class="button" type="submit">{{ __('auth.register') }}</button>
        </form>
        <x-oauth-buttons />
        <p>{{ __('auth.has_account') }} <a href="{{ route('login') }}">{{ __('auth.login') }}</a></p>
    </div>
</section>
@endsection
