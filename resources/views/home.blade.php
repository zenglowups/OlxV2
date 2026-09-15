@extends('layouts.app')

@section('content')
<section class="hero container" id="despre" aria-labelledby="hero-title">
    <div class="hero-copy">
        <div class="launch-status"><span aria-hidden="true"></span>{{ __('ui.status') }}</div>
        <p class="eyebrow">{{ __('ui.eyebrow') }}</p>
        <h1 id="hero-title">{{ __('ui.headline') }}<br><em>{{ __('ui.headline_accent') }}</em></h1>
        <p class="hero-intro">{{ __('ui.intro') }}</p>
        <a class="button button-primary" href="#principii">{{ __('ui.cta') }} <span aria-hidden="true">↗</span></a>
        <p class="local-note"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 10c0 5-7 10-7 10S5 15 5 10a7 7 0 1 1 14 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>{{ __('ui.local') }}</p>
    </div>
    <div class="hero-visual">
        <div class="illustration-frame">
            <img class="home-illustration" src="{{ asset('images/new-beginning.svg') }}" width="560" height="540" alt="{{ __('ui.image_label') }}" fetchpriority="high">
            <span class="art-tag">{{ __('ui.art_tag') }}</span>
        </div>
        <div class="handwritten-note">{{ __('ui.note') }}<br>{{ __('ui.note_second') }}<span aria-hidden="true">♡</span></div>
        <svg class="story-arrow" viewBox="0 0 100 72" fill="none" aria-hidden="true"><path d="M4 12c50-28 10 70 80 45m-14-8 17 6-10 14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
</section>

<section class="principles-section" id="principii" aria-labelledby="principles-title">
    <div class="container">
        <div class="section-heading">
            <div><p class="eyebrow">{{ __('ui.section_eyebrow') }}</p><h2 id="principles-title">{{ __('ui.section_title') }}</h2></div>
            <p>{{ __('ui.section_intro') }}</p>
        </div>
        <div class="principles-grid">
            <article class="principle">
                <div class="principle-top"><span class="principle-icon"><svg viewBox="0 0 28 28" aria-hidden="true"><path d="m8 14 4 4 8-9"/><rect x="4" y="4" width="20" height="20" rx="6"/></svg></span><span class="principle-number" aria-hidden="true">01</span></div>
                <h3>{{ __('ui.simple_title') }}</h3><p>{{ __('ui.simple_text') }}</p>
            </article>
            <article class="principle">
                <div class="principle-top"><span class="principle-icon gold"><svg viewBox="0 0 28 28" aria-hidden="true"><path d="m14 3 9 4v7c0 5-9 11-9 11S5 19 5 14V7l9-4Z"/><path d="m10 13 3 3 5-6"/></svg></span><span class="principle-number" aria-hidden="true">02</span></div>
                <h3>{{ __('ui.trust_title') }}</h3><p>{{ __('ui.trust_text') }}</p>
            </article>
            <article class="principle">
                <div class="principle-top"><span class="principle-icon"><svg viewBox="0 0 28 28" aria-hidden="true"><path d="M14 24S3 18 3 10c0-7 9-8 11-2 2-6 11-5 11 2 0 8-11 14-11 14Z"/></svg></span><span class="principle-number" aria-hidden="true">03</span></div>
                <h3>{{ __('ui.near_title') }}</h3><p>{{ __('ui.near_text') }}</p>
            </article>
        </div>
        <aside class="development-note" aria-labelledby="development-title">
            <span class="note-icon" aria-hidden="true">i</span>
            <div><h2 id="development-title">{{ __('ui.notice_title') }}</h2><p>{{ __('ui.notice_text') }}</p></div>
            <span class="status-pill">{{ __('ui.status') }}</span>
        </aside>
    </div>
</section>
@endsection
