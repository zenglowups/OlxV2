<x-mail::message>
@if (! empty($greeting))
# {{ $greeting }}
@endif
@foreach ($introLines as $line)
{{ $line }}

@endforeach
@isset($actionText)
<x-mail::button :url="$actionUrl" color="primary">{{ $actionText }}</x-mail::button>
@endisset
@foreach ($outroLines as $line)
{{ $line }}

@endforeach
{{ __('auth.regards') }}<br>
{{ config('app.name') }}
@isset($actionText)
<x-slot:subcopy>
{{ __('auth.mail_link_hint') }}<br>
<span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
