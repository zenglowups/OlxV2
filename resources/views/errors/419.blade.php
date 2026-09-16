@extends('layouts.app')
@section('title', __('auth.expired_title'))
@section('content')
<section class="auth-shell container"><div class="auth-intro"><h1>{{ __('auth.expired_title') }}</h1><p>{{ __('auth.expired_body') }}</p><a class="button button-primary" href="{{ route('login', ['locale' => app()->getLocale()]) }}">{{ __('auth.login') }}</a></div></section>
@endsection
