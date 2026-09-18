@if($photos->isNotEmpty())
<div class="listing-gallery">
@php($mainPhoto = $photos->first())
<a class="gallery-main" href="{{ $mainPhoto->imageUrl('full') }}" aria-label="{{ __('photo.open', ['number' => 1]) }}"><img src="{{ $mainPhoto->imageUrl('card') }}" srcset="{{ $mainPhoto->srcset() }}" sizes="(max-width: 720px) 90vw, 760px" width="{{ $mainPhoto->variants['full']['width'] }}" height="{{ $mainPhoto->variants['full']['height'] }}" alt="{{ $listing->title ?: __('photo.number', ['number' => 1]) }}" fetchpriority="high"></a>
@if($photos->count() > 1)<details class="gallery-more"><summary>{{ __('photo.all', ['count' => $photos->count()]) }}</summary><div class="gallery-thumbnails">@foreach($photos->skip(1) as $photo)<a href="{{ $photo->imageUrl('full') }}" aria-label="{{ __('photo.open', ['number' => $loop->iteration + 1]) }}"><img src="{{ $photo->imageUrl('thumb') }}" width="{{ $photo->variants['thumb']['width'] }}" height="{{ $photo->variants['thumb']['height'] }}" alt="{{ __('photo.number', ['number' => $loop->iteration + 1]) }}" loading="lazy" decoding="async"></a>@endforeach</div></details>@endif
</div>
@endif