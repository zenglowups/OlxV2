@extends('layouts.app')
@section('title', __('auth.account'))
@section('content')
<section class="auth-shell container">
    <div class="auth-intro"><p class="eyebrow">{{ __('auth.account') }}</p><h1>{{ auth()->user()->name }}</h1><p>{{ __('auth.account_intro') }}</p>
        <p>{{ __('auth.member_since', ['date' => auth()->user()->created_at->timezone(config('app.display_timezone'))->translatedFormat('F Y')]) }}</p>
        <span class="verified-badge">{{ __('auth.verified_label') }}</span>
    </div>
    <div class="form-card">
        <x-form-feedback />
        <dl class="account-details"><dt>{{ __('auth.email') }}</dt><dd>{{ auth()->user()->email }}</dd><dt>{{ __('auth.account_type') }}</dt><dd>{{ __('auth.'.auth()->user()->account_type) }}</dd></dl>
        <h2>{{ __('auth.security_title') }}</h2><p>{{ __('auth.revoke_intro') }}</p>
        <form method="POST" action="{{ route('sessions.revoke') }}">@csrf
            <x-form-field name="password" :label="__('auth.password')" type="password" autocomplete="current-password" required />
            <button class="button secondary" type="submit">{{ __('auth.revoke') }}</button>
        </form>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="button" type="submit">{{ __('auth.logout') }}</button></form>
    </div>
</section>
@endsection
