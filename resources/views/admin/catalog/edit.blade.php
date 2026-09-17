@extends('layouts.app')
@section('title', $category->exists ? __('catalog.edit') : __('catalog.new'))
@section('content')
<section class="catalog-page container">
<a class="text-link" href="{{ route('admin.categories.index') }}">← {{ __('catalog.back') }}</a>
<header class="page-heading"><p class="eyebrow">{{ __('catalog.admin') }}</p><h1>{{ $category->exists ? $category->label() : __('catalog.new') }}</h1></header>
<div class="admin-editor"><aside class="profile-sidebar"><h2>{{ __('catalog.parent_search') }}</h2><form method="GET"><x-form-field name="parent_q" :label="__('catalog.search')" :value="request('parent_q')" maxlength="100"/><button class="button secondary">{{ __('catalog.search_button') }}</button></form><p>{{ __('catalog.parent_hint') }}</p></aside>
<div class="form-card"><x-form-feedback/>
<form method="POST" action="{{ $category->exists ? route('admin.categories.update',$category) : route('admin.categories.store') }}">@csrf @if($category->exists) @method('PUT') @endif
@foreach(['name_ro','name_en','slug'] as $field)<x-form-field :name="$field" :label="__('catalog.'.$field)" :value="$category->$field" required maxlength="100"/>@endforeach
<div class="form-field"><label for="parent_id">{{ __('catalog.parent') }}</label><select name="parent_id" id="parent_id"><option value="">{{ __('catalog.root') }}</option>@foreach($parents as $parent)<option value="{{ $parent->id }}" @selected((string)old('parent_id',$category->parent_id)===(string)$parent->id)>{{ $parent->label() }}</option>@endforeach</select></div>
@foreach(['description_ro','description_en'] as $field)<div class="form-field"><label for="{{ $field }}">{{ __('catalog.'.$field) }}</label><textarea id="{{ $field }}" name="{{ $field }}" rows="3" maxlength="1500">{{ is_string(old($field,$category->$field)) ? old($field,$category->$field) : '' }}</textarea></div>@endforeach
<div class="form-field"><label for="icon">{{ __('catalog.icon') }}</label><select name="icon" id="icon">@foreach(['grid','car','parts','home','laptop','garden','fish','lamp','clothes','bike'] as $icon)<option @selected(old('icon',$category->icon)===$icon) value="{{ $icon }}">{{ __('catalog.icon_'.$icon) }}</option>@endforeach</select></div>
<x-form-field name="position" :label="__('catalog.position')" :value="$category->position" type="number" min="0" max="10000" required/>
<label class="checkbox-field"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$category->is_active))>{{ __('catalog.active') }}</label>
<x-form-field name="password" :label="__('auth.password')" :hint="__('catalog.password_hint')" type="password" autocomplete="current-password" required/>
<button class="button" type="submit">{{ __('catalog.save') }}</button>
</form></div></div>
@if($category->exists)<section class="attributes-panel"><div class="section-heading"><div><h2>{{ __('catalog.attributes') }}</h2><p>{{ __('catalog.inheritance') }}</p></div><a class="button secondary" href="{{ route('admin.attributes.create',$category) }}">{{ __('catalog.attribute_new') }} +</a></div><div class="admin-list">@forelse($attributes as $attribute)<article><div><h3>{{ $attribute->label() }}</h3><p>{{ __('catalog.type_'.$attribute->type) }} · {{ $attribute->is_active ? __('catalog.active') : __('catalog.inactive') }}</p></div><a class="text-link" href="{{ route('admin.attributes.edit',[$category,$attribute]) }}">{{ __('catalog.attribute_edit') }}</a></article>@empty<p>{{ __('catalog.no_attributes') }}</p>@endforelse</div>{{ $attributes->links('components.pagination') }}</section>@endif
</section>
@endsection
