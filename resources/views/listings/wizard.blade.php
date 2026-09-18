@extends('layouts.app')
@section('title', __('listing.heading_'.$step))
@section('content')
<section class="container sell-page">
<div class="wizard-topline"><a href="{{ route('drafts.index') }}">← {{ __('listing.exit') }}</a><span class="private-badge"><x-icon name="shield"/>{{ __('listing.draft') }}</span></div>
@include('listings.steps')
<div class="wizard-layout">
<div class="wizard-card">
<header><p class="eyebrow">{{ $listing->category->label() }}</p><h1>{{ __('listing.heading_'.$step) }}</h1><p>{{ __('listing.intro_'.$step) }}</p></header>
<x-form-feedback />
<form method="POST" action="{{ route('drafts.update', ['listing' => $listing, 'step' => $step]) }}" data-draft-form data-initial-dirty="{{ $errors->any() ? 'true' : 'false' }}" data-saved="{{ __('listing.saved') }}" data-saving="{{ __('listing.saving') }}" data-unsaved="{{ __('listing.unsaved') }}" data-failure="{{ __('listing.save_error') }}" data-conflict="{{ __('listing.conflict') }}">
@csrf
<input type="hidden" name="version" value="{{ $listing->version }}">
@if($step === 'details')
<x-form-field name="title" :label="__('listing.title')" :value="$listing->title" :hint="__('listing.title_hint')" maxlength="140" minlength="8" required />
<div class="form-field"><label for="description">{{ __('listing.description') }}</label><textarea id="description" name="description" rows="8" maxlength="5000" minlength="30" required aria-describedby="description-help" @error('description') aria-invalid="true" @enderror>{{ is_string(old('description', $listing->description)) ? old('description', $listing->description) : '' }}</textarea><p id="description-help" class="field-hint">{{ __('listing.description_hint') }}</p></div>
@elseif($step === 'attributes')
@forelse($definitions as $definition)
@php($value = old('attributes.'.$definition->key, $listing->attributes[$definition->key] ?? ''))
<div class="form-field">
<label for="attributes.{{ $definition->key }}">{{ $definition->label() }} @if($definition->unit) ({{ $definition->unit }}) @endif @unless($definition->is_required)<span class="optional-label">· {{ __('listing.optional') }}</span>@endunless</label>
@if(in_array($definition->type, ['select', 'boolean'], true))
<select id="attributes.{{ $definition->key }}" name="attributes[{{ $definition->key }}]" @required($definition->is_required)>
<option value="">{{ __('listing.select') }}</option>
@if($definition->type === 'boolean')
<option value="1" @selected(is_scalar($value) && (string) $value === '1')>{{ __('listing.yes') }}</option><option value="0" @selected($value !== '' && $value !== null && ! $value)>{{ __('listing.no') }}</option>
@else
@foreach($definition->options as $option)<option value="{{ $option['value'] }}" @selected($value === $option['value'])>{{ $option['label_'.app()->getLocale()] }}</option>@endforeach
@endif
</select>
@else
<input id="attributes.{{ $definition->key }}" name="attributes[{{ $definition->key }}]" type="{{ $definition->type === 'number' ? 'number' : 'text' }}" value="{{ is_scalar($value) ? $value : '' }}" @if($definition->type === 'number') step="any" @if($definition->minimum !== null) min="{{ $definition->minimum }}" @endif @if($definition->maximum !== null) max="{{ $definition->maximum }}" @endif @else maxlength="200" @endif @required($definition->is_required)>
@endif
@error('attributes.'.$definition->key)<p class="field-error">{{ $message }}</p>@enderror
</div>
@empty<p class="notice">{{ __('listing.no_attributes') }}</p>@endforelse
@elseif($step === 'price')
<fieldset class="choice-group"><legend>{{ __('listing.pricing_mode') }}</legend><div class="price-choices">@foreach(\App\Services\Listings\DraftValidator::PRICING as $mode)<label class="choice-card"><input type="radio" name="pricing_mode" value="{{ $mode }}" @checked(old('pricing_mode', $listing->pricing_mode) === $mode)><span>{{ __('listing.price_'.$mode) }}</span></label>@endforeach</div></fieldset>
<div data-price-fields class="price-fields">
<x-form-field name="price" :label="__('listing.price')" :value="$listing->priceInput()" :hint="__('listing.price_hint')" inputmode="decimal" maxlength="12" />
<div class="form-field"><label for="currency">{{ __('listing.currency') }}</label><select id="currency" name="currency">@foreach(config('listings.currencies') as $currency)<option value="{{ $currency }}" @selected(old('currency', $listing->currency) === $currency)>{{ $currency }}</option>@endforeach</select></div></div>
@elseif($step === 'location')
<div class="form-field"><label for="country_code">{{ __('listing.country_code') }}</label><select id="country_code" name="country_code"><option value="RO">{{ __('listing.romania') }}</option></select></div>
<div class="form-field"><label for="county_id">{{ __('listing.county_id') }}</label><select id="county_id" name="county_id" required><option value="">{{ __('listing.select') }}</option>@foreach($counties as $county)<option value="{{ $county->id }}" @selected((is_scalar(old('county_id', $listing->county_id)) ? (string) old('county_id', $listing->county_id) : '') === (string) $county->id)>{{ $county->name }}</option>@endforeach</select></div>
<x-form-field name="locality" :label="__('listing.locality')" :value="$listing->locality" :hint="__('listing.locality_hint')" maxlength="100" autocomplete="address-level2" required />
@elseif($step === 'contact')
<fieldset class="choice-group"><legend>{{ __('listing.contact_method') }}</legend><div class="contact-choices">@foreach(['email' => 'mail', 'phone' => 'user'] as $method => $icon)<label class="choice-card"><input type="radio" name="contact_method" value="{{ $method }}" @checked(old('contact_method', $listing->contact_method) === $method) required><x-icon :name="$icon"/><span><strong>{{ __('listing.'.$method) }}</strong><small>{{ __('listing.'.$method.'_help') }}</small></span></label>@endforeach</div></fieldset>
<div class="contact-privacy"><x-icon name="shield"/><p>{{ __('listing.contact_privacy') }}</p></div><a class="text-link" href="{{ route('profile.edit') }}">{{ __('listing.edit_profile') }} <span aria-hidden="true">↗</span></a>
@endif
<div class="autosave-status"><span role="status" data-save-status>{{ __('listing.saved') }}</span><button type="button" class="text-button" data-retry-save hidden>{{ __('listing.retry') }}</button><a href="{{ url()->current() }}" data-reload-draft hidden>{{ __('listing.reload') }}</a></div>
<div class="notice error" data-save-errors role="alert" hidden></div>
<div class="wizard-actions"><a class="button secondary" href="{{ route('drafts.edit', ['listing' => $listing, 'step' => $steps[array_search($step, $steps, true) - 1]]) }}">← {{ __('listing.back') }}</a><button class="button button-primary" type="submit" name="intent" value="next">{{ __('listing.next') }} <span aria-hidden="true">→</span></button></div>
<button class="text-button save-close" type="submit" name="intent" value="exit" formnovalidate>{{ __('listing.save_exit') }}</button>
</form>
</div>
<aside class="wizard-advice"><span class="advice-symbol"><x-icon name="edit"/></span><p class="eyebrow">{{ config('app.name') }}</p><h2>{{ __('listing.tips_title') }}</h2><p>{{ __('listing.tip_'.$step) }}</p><div class="advice-privacy"><x-icon name="shield"/><span>{{ __('listing.private_note') }}</span></div></aside>
</div>
</section>
@endsection