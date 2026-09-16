@extends('layouts.app')
@section('title', __('auth.denied_title'))
@section('content')
<section class="auth-shell container"><div class="auth-intro"><h1>{{ __('auth.denied_title') }}</h1><p>{{ __('auth.denied_body') }}</p><a class="button button-primary" href="{{ route('home.localized', ['locale' => app()->getLocale()]) }}">{{ __('ui.back_home') }}</a></div></section>
@endsection
