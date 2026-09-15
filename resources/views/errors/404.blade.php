@extends('layouts.app')
@section('title', __('ui.error_404'))
@section('content')
<section class="container error-page">
    <p class="eyebrow">404</p>
    <h1>{{ __('ui.error_404') }}</h1>
    <a class="button button-primary" href="{{ route('home.localized', ['locale' => app()->getLocale()]) }}">{{ __('ui.back_home') }} <span aria-hidden="true">↗</span></a>
</section>
@endsection
