@props(['category'])
<a class="category-card" href="{{ route('categories.show',$category->slug) }}"><span class="category-icon"><x-icon :name="$category->icon"/></span><strong>{{ $category->label() }}</strong><span class="category-arrow" aria-hidden="true">↗</span></a>
