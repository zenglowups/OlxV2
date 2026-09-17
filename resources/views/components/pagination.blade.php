@if($paginator->hasPages())
<nav class="pagination" aria-label="{{ __('catalog.pages') }}"><div>@if($paginator->onFirstPage())<span>{{ __('catalog.previous') }}</span>@else<a class="button secondary" href="{{ $paginator->previousPageUrl() }}" rel="prev">{{ __('catalog.previous') }}</a>@endif</div><div>@if($paginator->hasMorePages())<a class="button secondary" href="{{ $paginator->nextPageUrl() }}" rel="next">{{ __('catalog.next') }}</a>@endif</div></nav>
@endif
