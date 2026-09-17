@if(isset($categories) && $categories->isNotEmpty())
<section class="home-categories container" aria-labelledby="categories-title">
<div class="section-heading"><div><p class="eyebrow">{{ __('catalog.all') }}</p><h2 id="categories-title">{{ __('catalog.home_title') }}</h2></div><a class="text-link" href="{{ route('categories.index') }}">{{ __('catalog.all') }} <span aria-hidden="true">→</span></a></div>
<div class="category-grid">@foreach($categories as $category)<x-category-card :category="$category"/>@endforeach</div>
</section>
@endif
