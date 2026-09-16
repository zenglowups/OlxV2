@extends('layouts.app')
@section('title', __('auth.forgot_title'))
@section('content')
<section class="auth-shell container">
    <div class="auth-intro"><h1>{{ __('auth.forgot_title') }}</h1><p>{{ __('auth.forgot_body') }}</p></div>
    <div class="form-card">
        <x-form-feedback />
        <form method="POST" action="{{ route('password.email') }}">@csrf
            <x-form-field name="email" :label="__('auth.email')" type="email" autocomplete="email" required maxlength="254" />
            <button class="button" type="submit">{{ __('auth.send_reset') }}</button>
        </form>
        <a class="text-link" href="{{ route('login') }}">{{ __('auth.back_login') }}</a>
    </div>
</section>
@endsection
