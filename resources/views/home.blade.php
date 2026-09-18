@extends('layouts.app')
@section('content')
<section class="welcome container" id="despre" aria-labelledby="hero-title">
    <div class="welcome-card">
        <div class="welcome-copy">
            <p class="eyebrow">{{ __('ui.eyebrow') }}</p>
            <h1 id="hero-title">{{ __('ui.headline') }}<br><span>{{ __('ui.headline_accent') }}</span></h1>
            <p class="welcome-intro">{{ __('ui.intro') }}</p>
            <div class="welcome-actions">
                <a class="button button-primary" href="{{ auth()->check() ? route('profile.edit') : route('register') }}">{{ auth()->check() ? __('profile.profile_link') : __('ui.start') }} <span aria-hidden="true">↗</span></a>
                <a class="text-link" href="#principii">{{ __('ui.cta') }} <span aria-hidden="true">↓</span></a>
            </div>
            <p class="welcome-note"><span class="small-check" aria-hidden="true">✓</span>{{ __('ui.local') }}</p>
        </div>
        <div class="welcome-photo">
            <picture><source srcset="{{ asset('images/aproape-home.webp') }}" type="image/webp"><img class="home-illustration" src="{{ asset('images/new-beginning.svg') }}" alt="{{ __('ui.image_label') }}" width="1536" height="1024" fetchpriority="high"></picture>
            <div class="photo-caption"><span class="caption-mark" aria-hidden="true">♡</span><div>{{ __('ui.note') }}<strong>{{ __('ui.note_second') }}</strong></div></div>
        </div>
    </div>
    <div class="confidence-strip">
        <div><x-icon name="shield"/><span>{{ __('ui.strip_one') }}<small>{{ __('ui.strip_one_detail') }}</small></span></div>
        <div><x-icon name="user"/><span>{{ __('ui.strip_two') }}<small>{{ __('ui.strip_two_detail') }}</small></span></div>
        <div><x-icon name="heart"/><span>{{ __('ui.strip_three') }}<small>{{ __('ui.strip_three_detail') }}</small></span></div>
    </div>
</section>

@include('catalog.home-section')
@if($latestListings->isNotEmpty())<section class="market-section container"><div class="market-section-header"><h2>{{ __('market.latest') }}</h2><a href="{{ route('listings.index') }}">{{ __('market.all') }} →</a></div><div class="market-grid">@foreach($latestListings as $listing)@include('listings.card')@endforeach</div></section>@endif
<section class="home-section container" id="principii" aria-labelledby="principles-title">
    <div class="section-heading"><div><p class="eyebrow">{{ __('ui.section_eyebrow') }}</p><h2 id="principles-title">{{ __('ui.section_title') }}</h2></div><p>{{ __('ui.section_intro') }}</p></div>
    <div class="principles-grid">
        @foreach(['simple' => 'user', 'trust' => 'shield', 'near' => 'heart'] as $key => $icon)
        <article class="principle">
            <div class="principle-top"><span class="principle-icon {{ $key === 'trust' ? 'gold' : '' }}"><x-icon :name="$icon"/></span><span class="principle-number" aria-hidden="true">0{{ $loop->iteration }}</span></div>
            <h3>{{ __('ui.'.$key.'_title') }}</h3><p>{{ __('ui.'.$key.'_text') }}</p>
            @if($loop->first)<a class="text-link" href="{{ auth()->check() ? route('profile.edit') : route('register') }}">{{ auth()->check() ? __('profile.edit') : __('ui.start') }} <span aria-hidden="true">→</span></a>@endif
        </article>
        @endforeach
    </div>
</section>
<section class="join-section container">
    <div class="join-card"><div><p class="eyebrow">{{ __('ui.join_eyebrow') }}</p><h2>{{ __('ui.join_title') }}</h2><p>{{ __('ui.join_text') }}</p></div><a class="button button-primary" href="{{ auth()->check() ? route('account') : route('register') }}">{{ auth()->check() ? __('auth.account') : __('ui.start') }} <span aria-hidden="true">→</span></a></div>
    <p class="availability-note">{{ __('market.early_access') }}</p>
</section>
@endsection
