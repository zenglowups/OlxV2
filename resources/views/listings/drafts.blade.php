@extends('layouts.app')
@section('title', __('listing.drafts'))
@section('content')
<section class="container drafts-page">
<header class="account-welcome"><div><p class="eyebrow">{{ __('auth.account') }}</p><h1>{{ __('listing.drafts') }}</h1><p>{{ __('listing.drafts_intro') }}</p></div><a class="button button-primary" href="{{ route('drafts.create') }}"><span aria-hidden="true">+</span>{{ __('listing.new_draft') }}</a></header>
<p class="draft-management-link"><a class="text-link" href="{{ route('listings.manage') }}">{{ __('market.mine') }} →</a></p><x-form-feedback />
@if($drafts->isEmpty())
<div class="draft-empty"><span class="draft-empty-icon"><x-icon name="edit"/></span><h2>{{ __('listing.empty_title') }}</h2><p>{{ __('listing.empty_text') }}</p><a class="button button-primary" href="{{ route('drafts.create') }}">{{ __('listing.new_draft') }} <span aria-hidden="true">→</span></a></div>
@else
<div class="draft-grid">
@foreach($drafts as $listing)
<article class="draft-card">@if($image = $listing->photos->first())<div class="draft-card-photo"><img src="{{ $image->imageUrl('card') }}" width="{{ $image->variants['card']['width'] }}" height="{{ $image->variants['card']['height'] }}" alt="{{ $listing->title ?: __('listing.untitled') }}" loading="lazy" decoding="async"><span class="private-badge"><x-icon name="shield"/>{{ __('listing.draft') }}</span></div>@else<div class="draft-card-symbol"><x-icon :name="$listing->category->icon"/><span class="private-badge"><x-icon name="shield"/>{{ __('listing.draft') }}</span></div>@endif<div class="draft-card-body"><p class="eyebrow">{{ $listing->category->label() }}</p><h2>{{ $listing->title ?: __('listing.untitled') }}</h2><strong class="draft-price">{{ $listing->priceLabel() }}</strong><p class="draft-date">{{ __('listing.updated', ['date' => $listing->updated_at->timezone(config('app.display_timezone'))->translatedFormat('j M, H:i')]) }}</p><a class="button secondary" href="{{ route('drafts.edit', ['listing' => $listing, 'step' => $listing->last_step]) }}">{{ __('listing.resume') }} <span aria-hidden="true">→</span></a></div></article>
@endforeach
</div>
{{ $drafts->links('components.pagination') }}
@endif
</section>
@endsection