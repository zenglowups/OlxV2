@extends('layouts.app')
@section('title', __('auth.too_many'))
@section('content')
<section class="auth-shell container"><div class="auth-intro"><h1>{{ __('auth.too_many') }}</h1><p>{{ __('auth.try_later') }}</p><a class="button" href="{{ route('home.localized', ['locale' => app()->getLocale()]) }}">{{ __('ui.back_home') }}</a></div></section>
@endsection
