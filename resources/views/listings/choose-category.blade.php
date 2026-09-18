@extends('layouts.app')
@section('title', __('listing.choose_title'))
@section('content')
<section class="container sell-page">
<div class="wizard-topline"><a href="{{ route('drafts.index') }}">← {{ __('listing.exit') }}</a><span class="private-badge"><x-icon name="shield"/>{{ __('listing.draft') }}</span></div>
<header class="sell-heading"><p class="eyebrow">{{ __('listing.step_count', ['current' => 1, 'total' => count(\App\Services\Listings\DraftValidator::STEPS)]) }}</p><h1>{{ __('listing.choose_title') }}</h1><p>{{ __('listing.choose_intro') }}</p></header>
<div class="category-picker"><x-form-feedback />
<nav class="breadcrumbs" aria-label="{{ __('catalog.breadcrumb') }}"><a href="{{ route('drafts.create', ['draft' => $draft?->public_id]) }}">{{ __('catalog.all') }}</a>@foreach($ancestors as $ancestor)<span aria-hidden="true">›</span><a href="{{ route('drafts.create', ['category' => $ancestor->slug, 'draft' => $draft?->public_id]) }}" @if($loop->last) aria-current="page" @endif>{{ $ancestor->label() }}</a>@endforeach</nav>
@if($categories->isNotEmpty())
<h2>{{ $category?->label() ?? __('catalog.all') }}</h2>
<div class="sell-category-grid">@foreach($categories as $item)<a class="sell-category" href="{{ route('drafts.create', ['category' => $item->slug, 'draft' => $draft?->public_id]) }}"><span class="category-icon"><x-icon :name="$item->icon"/></span><strong>{{ $item->label() }}</strong><span class="category-arrow" aria-hidden="true">→</span></a>@endforeach</div>
{{ $categories->links('components.pagination') }}
@elseif($category)
<div class="chosen-category"><span class="category-icon"><x-icon :name="$category->icon"/></span><h2>{{ $category->label() }}</h2><p>{{ __('listing.category_hint') }}</p>
<form method="POST" action="{{ $draft ? route('drafts.update', ['listing' => $draft, 'step' => 'category']) : route('drafts.store') }}">@csrf
<input type="hidden" name="category_id" value="{{ $category->id }}">
@if($draft)<input type="hidden" name="version" value="{{ $draft->version }}"><p class="field-hint">{{ __('listing.category_change') }}</p>@else<input type="hidden" name="creation_token" value="{{ \Illuminate\Support\Str::uuid() }}">@endif
<button type="submit" class="button button-primary">{{ __('listing.choose') }} <span aria-hidden="true">→</span></button>
</form></div>
@else<p>{{ __('listing.no_categories') }}</p>@endif
</div>
<p class="wizard-privacy"><x-icon name="shield"/>{{ __('listing.private_note') }}</p>
</section>
@endsection