@extends('layouts.app')
@section('title', __('auth.reset_title'))
@section('content')
<section class="auth-shell container"><div class="auth-intro"><h1>{{ __('auth.reset_title') }}</h1><p>{{ __('auth.reset_invalid') }}</p><a class="button button-primary" href="{{ route('password.request') }}">{{ __('auth.send_reset') }}</a></div></section>
@endsection
