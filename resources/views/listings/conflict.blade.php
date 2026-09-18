@extends('layouts.app')
@section('title', __('listing.conflict_title'))
@section('content')
<section class="container sell-page"><div class="wizard-card conflict-card"><h1>{{ __('listing.conflict_title') }}</h1><p role="alert">{{ __('listing.conflict') }}</p><p>{{ __('listing.conflict_help') }}</p><h2>{{ __('listing.submitted') }}</h2><dl class="conflict-values">@foreach($submitted as $key => $value)<dt>{{ __('listing.'.$key) }}</dt><dd>{{ is_scalar($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</dd>@endforeach</dl><a class="button button-primary" href="{{ route('drafts.edit', ['listing' => $listing, 'step' => $step]) }}">{{ __('listing.reload') }}</a></div></section>
@endsection