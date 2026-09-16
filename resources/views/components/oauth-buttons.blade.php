@foreach(['google' => 'Google', 'facebook' => 'Facebook'] as $provider => $label)
    @if(\App\Http\Controllers\Auth\OAuthController::enabled($provider))
        <a class="button secondary" href="{{ route('oauth.redirect', ['provider' => $provider]) }}">{{ __('auth.oauth', ['provider' => $label]) }}</a>
    @endif
@endforeach
