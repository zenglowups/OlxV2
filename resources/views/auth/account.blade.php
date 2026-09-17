@extends('layouts.app')
@section('title', __('auth.account'))
@section('content')
<section class="account-workspace container">
    <header class="account-welcome"><div><p class="eyebrow">{{ __('auth.account') }}</p><h1>{{ auth()->user()->name }}</h1><p>{{ __('auth.account_intro') }}</p></div><a class="button button-primary" href="{{ route('profile.edit') }}"><x-icon name="edit"/>{{ __('profile.edit') }}</a></header>
    <div class="account-summary">
        <article><span class="summary-icon"><x-icon name="shield"/></span><div><strong>{{ __('auth.verified_label') }}</strong><p>{{ __('auth.email') }}</p></div></article>
        <article><span class="summary-icon"><x-icon name="calendar"/></span><div><strong>{{ auth()->user()->created_at->timezone(config('app.display_timezone'))->translatedFormat('F Y') }}</strong><p>{{ __('auth.member_since', ['date' => '']) }}</p></div></article>
        <article><span class="summary-icon gold"><x-icon name="user"/></span><div><strong>{{ __('auth.'.auth()->user()->account_type) }}</strong><p>{{ __('auth.account_type') }}</p></div></article>
    </div>
    <div class="account-columns">
        <aside class="account-panel"><span class="principle-icon"><x-icon name="user"/></span><h2>{{ __('profile.profile_link') }}</h2><p>{{ __('profile.intro') }}</p><a class="button secondary" href="{{ route('profile.edit') }}">{{ __('profile.edit') }} <span aria-hidden="true">→</span></a><dl class="account-details"><dt>{{ __('auth.email') }}</dt><dd>{{ auth()->user()->email }}</dd></dl><form method="POST" action="{{ route('logout') }}">@csrf<button class="text-button" type="submit">{{ __('auth.logout') }} <span aria-hidden="true">↗</span></button></form></aside>
        <div class="form-card security-card">
            <x-form-feedback />
            <p class="eyebrow">{{ __('auth.account') }}</p><h2>{{ __('auth.security_title') }}</h2><p>{{ __('auth.revoke_intro') }}</p>
            <form method="POST" action="{{ route('sessions.revoke') }}">@csrf
                <x-form-field name="password" :label="__('auth.password')" type="password" autocomplete="current-password" required />
                <button class="button secondary" type="submit"><x-icon name="shield"/>{{ __('auth.revoke') }}</button>
            </form>
        </div>
    </div>
</section>
@endsection
