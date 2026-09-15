@extends('layouts.app')
@section('title', __('ui.error_500'))
@section('content')
<section class="container error-page">
    <p class="eyebrow">500</p>
    <h1>{{ __('ui.error_500') }}</h1>
    <a class="button button-primary" href="{{ route('home.localized', ['locale' => app()->getLocale()]) }}">{{ __('ui.back_home') }}</a>
</section>
@endsection
