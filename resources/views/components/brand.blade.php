<a class="brand" href="{{ route('home.localized', ['locale' => app()->getLocale()]) }}" aria-label="{{ config('app.name') }} — {{ __('ui.home') }}">
    <svg class="brand-symbol" viewBox="0 0 40 40" fill="none" aria-hidden="true"><path d="M9 28V17a11 11 0 0 1 22 0v11" stroke="currentColor" stroke-width="6" stroke-linecap="round"/><path d="M9 28c0 7 11 7 11 0V17" stroke="currentColor" stroke-width="6" stroke-linecap="round"/><circle cx="31" cy="33" r="3" fill="currentColor"/></svg>
    <span>{{ config('app.name') }}<span class="brand-dot">.</span></span>
</a>
