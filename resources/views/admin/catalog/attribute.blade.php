@extends('layouts.app')
@section('title', __('catalog.attribute_edit'))
@section('content')
<section class="catalog-page container">
<a class="text-link" href="{{ route('admin.categories.edit',$category) }}">← {{ $category->label() }}</a>
<header class="page-heading"><p class="eyebrow">{{ __('catalog.attributes') }}</p><h1>{{ $attribute->exists ? __('catalog.attribute_edit') : __('catalog.attribute_new') }}</h1></header>
<div class="form-card attribute-form"><x-form-feedback/>
<form method="POST" action="{{ $attribute->exists ? route('admin.attributes.update',[$category,$attribute]) : route('admin.attributes.store',$category) }}">@csrf @if($attribute->exists) @method('PUT') @endif
<x-form-field name="key" :label="__('catalog.key')" :hint="__('catalog.key_hint')" :value="$attribute->key" required maxlength="48" :readonly="$attribute->exists"/>
@foreach(['label_ro','label_en'] as $field)<x-form-field :name="$field" :label="__('catalog.'.$field)" :value="$attribute->$field" required maxlength="100"/>@endforeach
<div class="form-field"><label for="type">{{ __('catalog.type') }}</label><select name="type" id="type">@foreach(['text','number','select','boolean'] as $type)@if(!$attribute->exists || $attribute->type===$type)<option value="{{ $type }}" @selected(old('type',$attribute->type)===$type)>{{ __('catalog.type_'.$type) }}</option>@endif @endforeach</select></div>
<x-form-field name="unit" :label="__('catalog.unit')" :value="$attribute->unit" maxlength="20"/>
@foreach(['minimum','maximum'] as $field)<x-form-field :name="$field" :label="__('catalog.'.$field)" :value="$attribute->$field" type="number" step="any"/>@endforeach
<div class="form-field"><label for="options_lines">{{ __('catalog.options') }}</label><textarea id="options_lines" name="options_lines" rows="5" maxlength="10000" aria-describedby="options-help">{{ is_string(old('options_lines')) ? old('options_lines') : collect($attribute->options ?? [])->map(fn($o) => $o['value'].'|'.$o['label_ro'].'|'.$o['label_en'])->implode("\n") }}</textarea><p class="field-hint" id="options-help">{{ __('catalog.options_hint') }}</p></div>
<x-form-field name="position" :label="__('catalog.position')" :value="$attribute->position" type="number" min="0" max="10000" required/>
@foreach(['is_active'=>'active','is_required'=>'required','is_filterable'=>'filterable'] as $field=>$label)<label class="checkbox-field"><input type="checkbox" name="{{ $field }}" value="1" @checked(old($field,$attribute->$field))>{{ __('catalog.'.$label) }}</label>@endforeach
<x-form-field name="password" :label="__('auth.password')" :hint="__('catalog.password_hint')" type="password" autocomplete="current-password" required/>
<button class="button" type="submit">{{ __('catalog.save') }}</button>
</form></div></section>
@endsection
