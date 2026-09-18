@extends('layouts.app')
@section('title', $category?->label() ?? __('catalog.title'))
@section('content')
<section class="catalog-page container">
<nav class="breadcrumbs" aria-label="{{ __('catalog.breadcrumb') }}"><a href="{{ route('home.localized') }}">{{ __('ui.home') }}</a><span aria-hidden="true">›</span><a href="{{ route('categories.index') }}">{{ __('catalog.all') }}</a>@foreach($ancestors as $ancestor)<span aria-hidden="true">›</span><a href="{{ route('categories.show',$ancestor->slug) }}" @if($loop->last) aria-current="page" @endif>{{ $ancestor->label() }}</a>@endforeach</nav>
<header class="catalog-heading"><span class="category-icon"><x-icon :name="$category?->icon ?? 'grid'"/></span><div><p class="eyebrow">{{ __('catalog.browse') }}</p><h1>{{ $category?->label() ?? __('catalog.title') }}</h1><p>{{ $category?->description() ?: __('catalog.intro') }}</p></div></header>
@if($categories->isNotEmpty())
<h2 class="catalog-subtitle">{{ $category ? __('catalog.children') : __('catalog.all') }}</h2>
<div class="category-grid">@foreach($categories as $item)<x-category-card :category="$item"/>@endforeach</div>
{{ $categories->links('components.pagination') }}
@elseif($category && ($listings ?? collect())->isEmpty())
<div class="catalog-empty"><x-icon name="heart"/><h2>{{ __('catalog.leaf_title') }}</h2><p>{{ __('catalog.leaf_text') }}</p><a class="button button-primary" href="{{ route('drafts.create', ['category' => $category->slug]) }}">{{ __('listing.start') }}</a></div>
@elseif(!$category)
<p class="catalog-empty">{{ __('listing.no_categories') }}</p>
@endif
@if(isset($listings) && $listings->isNotEmpty())<section class="market-section"><div class="market-section-header"><h2>{{ __('market.category_listings') }}</h2></div><div class="market-grid">@foreach($listings as $listing)@include('listings.card')@endforeach</div>{{ $listings->links('components.pagination') }}</section>@endif
</section>
@endsection
