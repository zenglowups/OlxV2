<article class="market-card">
<a class="market-card-image" href="{{ route('listings.show', $listing) }}">
@if($photo = $listing->photos->first())<img src="{{ $photo->imageUrl('card') }}" width="{{ $photo->variants['card']['width'] }}" height="{{ $photo->variants['card']['height'] }}" alt="{{ $listing->title }}" loading="lazy" decoding="async">
@else<span class="market-placeholder"><x-icon :name="$listing->category->icon"/><span>{{ __('market.no_photo') }}</span></span>@endif
@if($listing->status === 'reserved')<span class="listing-state state-reserved">{{ __('market.status_reserved') }}</span>@endif
</a><div class="market-card-body"><p class="eyebrow">{{ $listing->category->label() }}</p><h3><a href="{{ route('listings.show', $listing) }}">{{ $listing->title }}</a></h3><strong class="market-price">{{ $listing->priceLabel() }}</strong><p class="market-card-location"><x-icon name="home"/>{{ $listing->locality }}@if($listing->county), {{ $listing->county->name }}@endif</p><p class="market-card-date">{{ __('market.published_on', ['date' => $listing->published_at->timezone(config('app.display_timezone'))->translatedFormat('j M Y')]) }}</p></div>
</article>
