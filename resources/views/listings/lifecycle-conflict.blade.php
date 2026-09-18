@extends('layouts.app')
@section('title', __('market.conflict_title'))
@section('content')
<section class="container lifecycle-page"><div class="lifecycle-card"><h1>{{ __('market.conflict_title') }}</h1><p>{{ __('market.conflict_text') }}</p><a class="button button-primary" href="{{ route('listings.manage') }}">{{ __('market.cancel') }}</a></div></section>
@endsection
