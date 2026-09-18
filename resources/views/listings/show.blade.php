@extends('layouts.app')
@section('title', $listing->title)
@section('content')
<section class="container public-listing-page">
<nav class="breadcrumbs" aria-label="{{ __('catalog.breadcrumb') }}"><a href="{{ route('listings.index') }}">{{ __('market.title') }}</a>@foreach($ancestors as $ancestor)<span aria-hidden="true">›</span><a href="{{ route('categories.show',$ancestor->slug) }}">{{ $ancestor->label() }}</a>@endforeach</nav>
<x-form-feedback />
@if(auth()->id() === $listing->user_id)<div class="owner-listing-note"><span>{{ __('market.owner_note') }}</span><a href="{{ route('listings.manage') }}">{{ __('market.mine') }} →</a></div>@endif
<div class="public-listing-layout">
<article class="listing-preview public-listing-main">
@include('listings.gallery')
<div class="listing-meta"><span class="eyebrow">{{ $listing->category->label() }}</span><span>{{ __('market.published_on', ['date'=>$listing->published_at->timezone(config('app.display_timezone'))->translatedFormat('j M Y')]) }}</span></div>
<h1>{{ $listing->title }}</h1><p class="preview-price">{{ $listing->priceLabel() }} @if($listing->pricing_mode === 'negotiable')<small>{{ __('listing.price_negotiable') }}</small>@endif</p>
<p class="preview-location"><x-icon name="home"/>{{ $listing->locality }}, {{ $listing->county->name }}</p>
@if($listing->status === 'reserved')<p class="reserved-notice"><span class="listing-state state-reserved">{{ __('market.status_reserved') }}</span>{{ __('market.reserved_note') }}</p>@endif
<section><h2>{{ __('listing.preview_description') }}</h2><p class="listing-description">{{ $listing->description }}</p></section>
@if($definitions->isNotEmpty())<section><h2>{{ __('listing.preview_details') }}</h2><dl class="listing-attributes">@foreach($definitions as $definition)@php($value = $listing->attributes[$definition->key] ?? null)@if($value !== null)<div><dt>{{ $definition->label() }}</dt><dd>@if($definition->type === 'boolean'){{ __($value ? 'listing.yes' : 'listing.no') }}@elseif($definition->type === 'select'){{ collect($definition->options)->firstWhere('value', $value)['label_'.app()->getLocale()] ?? __('listing.not_set') }}@else{{ $value }} {{ $definition->unit }}@endif</dd></div>@endif
@endforeach</dl></section>@endif
</article>
<aside class="public-listing-sidebar">
<section class="public-seller-card"><p class="eyebrow">{{ __('market.seller') }}</p><div class="public-seller-identity">
@if($listing->user->profile->avatar_id)<img src="{{ route('avatar',['avatar'=>$listing->user->profile->avatar_id]) }}" width="64" height="64" alt="{{ __('profile.photo_alt',['name'=>$listing->user->name]) }}">
@else<span class="avatar-initial" aria-hidden="true">{{ mb_strtoupper(mb_substr($listing->user->name,0,1)) }}</span>@endif
<div><h2>{{ $listing->user->account_type === 'business' ? $listing->user->profile->business_name : $listing->user->name }}</h2><p>{{ __('auth.'.$listing->user->account_type) }}</p></div></div>
<p class="seller-membership">{{ __('profile.member_since',['date'=>$listing->user->created_at->timezone(config('app.display_timezone'))->translatedFormat('F Y')]) }}</p>
<span class="verified-badge"><x-icon name="shield"/>{{ __('profile.email_verified') }}</span>
<a class="button secondary" href="{{ route('profile.show',$listing->user->profile) }}">{{ __('market.profile') }} <span aria-hidden="true">→</span></a>
<p class="contact-unavailable">{{ __('market.contact_pending') }}</p>
</section>
<section class="listing-safety"><span class="advice-symbol"><x-icon name="shield"/></span><h2>{{ __('market.safety_title') }}</h2><p>{{ __('market.safety_text') }}</p></section>
</aside></div>
</section>
@endsection
