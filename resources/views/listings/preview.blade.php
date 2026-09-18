@extends('layouts.app')
@section('title', __('listing.heading_preview'))
@section('content')
<section class="container sell-page">
<div class="wizard-topline"><a href="{{ route('drafts.index') }}">← {{ __('listing.exit') }}</a><span class="private-badge"><x-icon name="shield"/>{{ __('listing.draft') }}</span></div>
@include('listings.steps')
<header class="sell-heading"><h1>{{ __('listing.heading_preview') }}</h1><p>{{ __('listing.intro_preview') }}</p></header>
<div class="preview-layout">
<article class="listing-preview">
@include('listings.gallery')
<p class="eyebrow">{{ $listing->category->label() }}</p><h2>{{ $listing->title ?: __('listing.untitled') }}</h2>
<p class="preview-price">{{ $listing->priceLabel() }} @if($listing->pricing_mode === 'negotiable')<small>{{ __('listing.price_negotiable') }}</small>@endif</p>
<p class="preview-location"><x-icon name="home"/>{{ $listing->locality ?: __('listing.not_set') }}@if($listing->county), {{ $listing->county->name }}@endif</p>
<section><div class="preview-section-title"><h3>{{ __('listing.preview_description') }}</h3><a href="{{ route('drafts.edit', ['listing' => $listing, 'step' => 'details']) }}">{{ __('listing.edit_section') }}</a></div><p class="listing-description">{{ $listing->description ?: __('listing.not_set') }}</p></section>
@if($definitions->isNotEmpty())<section><div class="preview-section-title"><h3>{{ __('listing.preview_details') }}</h3><a href="{{ route('drafts.edit', ['listing' => $listing, 'step' => 'attributes']) }}">{{ __('listing.edit_section') }}</a></div><dl class="listing-attributes">@foreach($definitions as $definition)@php($value = $listing->attributes[$definition->key] ?? null)<div><dt>{{ $definition->label() }}</dt><dd>@if($value === null){{ __('listing.not_set') }}@elseif($definition->type === 'boolean'){{ __($value ? 'listing.yes' : 'listing.no') }}@elseif($definition->type === 'select'){{ collect($definition->options)->firstWhere('value', $value)['label_'.app()->getLocale()] ?? __('listing.not_set') }}@else{{ $value }} {{ $definition->unit }}@endif</dd></div>@endforeach</dl></section>@endif
<section><div class="preview-section-title"><h3>{{ __('listing.contact_method') }}</h3><a href="{{ route('drafts.edit', ['listing' => $listing, 'step' => 'contact']) }}">{{ __('listing.edit_section') }}</a></div><p>{{ $listing->contact_method ? __('listing.'.$listing->contact_method) : __('listing.not_set') }}</p><p class="field-hint">{{ __('listing.contact_privacy') }}</p></section>
</article>
<aside class="preview-checklist"><span class="advice-symbol"><x-icon name="shield"/></span><h2>{{ $issues ? __('listing.preview_missing') : __('listing.preview_ready') }}</h2><ul>@foreach(array_diff($steps, ['preview']) as $item)<li><a href="{{ route('drafts.edit', ['listing' => $listing, 'step' => $item]) }}"><span aria-hidden="true">{{ isset($issues[$item]) ? '○' : '✓' }}</span>{{ __('listing.step_'.$item) }}<small>{{ isset($issues[$item]) ? __('listing.not_set') : __('listing.edit_section') }}</small></a></li>@endforeach</ul><p>{{ __('photo.pending_publish') }}</p><a class="button button-primary" href="{{ route('drafts.index') }}">{{ __('listing.exit') }}</a></aside>
</div>
</section>
@endsection