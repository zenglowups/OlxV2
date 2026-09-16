@extends('layouts.app')
@section('title', __('auth.verify_title'))
@section('content')
<section class="auth-shell container">
    <div class="auth-intro"><h1>{{ __('auth.verify_title') }}</h1><p>{{ __('auth.verify_intro', ['email' => auth()->user()->email]) }}</p></div>
    <div class="form-card">
        <x-form-feedback />
        <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="button" type="submit">{{ __('auth.resend') }}</button></form>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="button secondary" type="submit">{{ __('auth.logout') }}</button></form>
    </div>
</section>
@endsection
