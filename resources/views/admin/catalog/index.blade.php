@extends('layouts.app')
@section('title', __('catalog.admin'))
@section('content')
<section class="catalog-page container"><div class="section-heading"><div><p class="eyebrow">{{ __('catalog.admin') }}</p><h1>{{ __('catalog.all') }}</h1></div><a class="button button-primary" href="{{ route('admin.categories.create') }}">{{ __('catalog.new') }} +</a></div>
<x-form-feedback/>
<form class="catalog-search" method="GET"><x-form-field name="q" :label="__('catalog.search')" :value="request('q')" maxlength="100"/><button class="button button-primary">{{ __('catalog.search_button') }}</button></form>
<div class="admin-list">
@forelse($categories as $category)
<article><div><h2>{{ $category->label() }}</h2><p>{{ $category->parent?->label() ?? __('catalog.root') }} · {{ $category->is_active ? __('catalog.active') : __('catalog.inactive') }}</p></div><div class="admin-row-actions"><a class="text-link" href="{{ route('admin.categories.edit',$category) }}">{{ __('catalog.edit') }}</a><a class="text-link" href="{{ route('admin.categories.create',['parent_id'=>$category->id]) }}">{{ __('catalog.add_child') }}</a></div></article>
@empty<p>{{ __('catalog.empty') }}</p>@endforelse
</div>{{ $categories->links('components.pagination') }}</section>
@endsection
