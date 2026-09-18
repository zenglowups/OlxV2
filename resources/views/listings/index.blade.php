@extends('layouts.app')
@section('title', __('market.title'))
@section('content')
<section class="container market-page">
<header class="market-heading"><div><p class="eyebrow">{{ config('app.name') }}</p><h1>{{ __('market.title') }}</h1><p>{{ __('market.intro') }}</p></div><a class="button secondary" href="{{ route('categories.index') }}"><x-icon name="grid"/>{{ __('catalog.all') }}</a></header>
@if($listings->isNotEmpty())<div class="market-grid">@foreach($listings as $listing)@include('listings.card')@endforeach</div>{{ $listings->links('components.pagination') }}
@else<div class="market-empty"><span class="advice-symbol"><x-icon name="heart"/></span><h2>{{ __('market.empty_title') }}</h2><p>{{ __('market.empty_text') }}</p><a class="button button-primary" href="{{ route('drafts.create') }}">{{ __('listing.start') }} +</a></div>@endif
</section>
@endsection
