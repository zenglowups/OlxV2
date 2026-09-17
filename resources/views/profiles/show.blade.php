@extends('layouts.app')
@section('title', $profile->user->account_type === 'business' ? $profile->business_name : $profile->user->name)
@section('content')
<section class="profile-workspace container">
    @if($preview)<div class="notice success">{{ __('profile.private_preview') }} <a class="text-link" href="{{ route('profile.edit') }}">{{ __('profile.edit') }}</a></div>@endif
    <div class="seller-hero">
        @if($profile->avatar_id)
            <img class="profile-avatar" src="{{ route('avatar', ['avatar' => $profile->avatar_id]) }}" width="144" height="144" alt="{{ __('profile.photo_alt', ['name' => $profile->user->name]) }}">
        @else
            <span class="avatar-initial" aria-hidden="true">{{ mb_strtoupper(mb_substr($profile->user->name, 0, 1)) }}</span>
        @endif
        <div class="seller-identity">
            <p class="eyebrow">{{ __('auth.'.$profile->user->account_type) }}</p>
            <h1>{{ $profile->user->account_type === 'business' ? $profile->business_name : $profile->user->name }}</h1>
            <p class="username">{{ '@'.$profile->username }}</p>
            <p>{{ __('profile.member_since', ['date' => $profile->user->created_at->timezone(config('app.display_timezone'))->translatedFormat('F Y')]) }}</p>
            <div class="real-badges">
                @if($profile->user->hasVerifiedEmail())<span class="verified-badge">{{ __('profile.email_verified') }}</span>@endif
                @if($profile->user->created_at->lte(now()->subYears(3)))<span class="verified-badge">{{ __('profile.member_three') }}</span>
                @elseif($profile->user->created_at->lte(now()->subYear()))<span class="verified-badge">{{ __('profile.member_year') }}</span>@endif
            </div>
        </div>
        @if(auth()->id() === $profile->user_id)<a class="button button-primary" href="{{ route('profile.edit') }}">{{ __('profile.edit') }}</a>@endif
    </div>
    <div class="seller-about">
        <h2>{{ __('profile.about') }}</h2>
        <p class="seller-description">{{ $profile->description ?: __('profile.no_description') }}</p>
        @if($profile->location_label)<p class="seller-location">{{ $profile->location_label }}</p>@endif
        @if($profile->user->account_type === 'business' && $profile->show_tax_id && $profile->tax_id)
            <div class="business-disclosure"><p>{{ __('profile.declared_business') }}</p><p>{{ __('profile.tax_id') }}: {{ $profile->tax_id }}</p></div>
        @endif
    </div>
</section>
@endsection
