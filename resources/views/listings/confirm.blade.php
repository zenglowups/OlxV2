@extends('layouts.app')
@section('title', __('market.action_'.$action))
@section('content')
<section class="container lifecycle-page"><div class="lifecycle-card"><p class="eyebrow">{{ __('market.mine') }}</p><h1>{{ __('market.action_'.$action) }}</h1><h2>{{ $listing->title ?: __('listing.untitled') }}</h2><p>{{ __('market.confirm_'.$action) }}</p><x-form-feedback /><form method="POST" action="{{ route('listings.transition',['listing'=>$listing,'action'=>$action]) }}">@csrf<input type="hidden" name="version" value="{{ $listing->version }}"><button type="submit" class="button button-primary">{{ __('market.action_'.$action) }}</button></form><a class="text-link" href="{{ route('listings.manage') }}">← {{ __('market.cancel') }}</a></div></section>
@endsection
