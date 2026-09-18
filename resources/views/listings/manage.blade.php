@extends('layouts.app')
@section('title', __('market.mine'))
@section('content')
<section class="container manage-page">
<header class="market-heading"><div><p class="eyebrow">{{ __('auth.account') }}</p><h1>{{ __('market.mine') }}</h1><p>{{ __('market.manage_intro') }}</p></div><a class="button button-primary" href="{{ route('drafts.create') }}">{{ __('listing.start') }} +</a></header>
<x-form-feedback />
<div class="listing-summary">@foreach(['active'=>'grid','reserved'=>'calendar','sold'=>'shield','draft'=>'edit'] as $key=>$icon)<a href="{{ route('listings.manage',['status'=>$key]) }}"><span class="summary-icon @if($key === 'sold') gold @endif"><x-icon :name="$icon"/></span><span><strong>{{ $counts[$key] ?? 0 }}</strong><small>{{ __('market.status_'.$key) }}</small></span></a>@endforeach</div>
<nav class="listing-tabs" aria-label="{{ __('market.state_filter') }}"><a href="{{ route('listings.manage') }}" @if(!$status) aria-current="page" @endif>{{ __('market.total') }} <span>{{ $counts->sum() }}</span></a>@foreach(\App\Models\Listing::STATES as $state)<a href="{{ route('listings.manage',['status'=>$state]) }}" @if($status === $state) aria-current="page" @endif>{{ __('market.status_'.$state) }} <span>{{ $counts[$state] ?? 0 }}</span></a>@endforeach</nav>
@if($listings->isEmpty())<div class="market-empty"><span class="advice-symbol"><x-icon name="edit"/></span><h2>{{ __('market.manage_empty') }}</h2><a class="button secondary" href="{{ route('drafts.create') }}">{{ __('listing.start') }} +</a></div>
@else
<div class="manage-list">
@foreach($listings as $listing)
<article class="manage-listing" data-listing-id="{{ $listing->public_id }}">
<div class="manage-photo">@if($photo = $listing->photos->first())<img src="{{ $photo->imageUrl('thumb') }}" width="{{ $photo->variants['thumb']['width'] }}" height="{{ $photo->variants['thumb']['height'] }}" alt="{{ $listing->title ?: __('listing.untitled') }}" loading="lazy">@else<x-icon :name="$listing->category->icon"/>@endif</div>
<div class="manage-listing-title"><p class="eyebrow">{{ $listing->category->label() }}</p><h2>{{ $listing->title ?: __('listing.untitled') }}</h2><strong>{{ $listing->priceLabel() }}</strong><p>{{ __('listing.updated',['date'=>$listing->updated_at->timezone(config('app.display_timezone'))->translatedFormat('j M Y, H:i')]) }}</p></div>
<div class="manage-listing-status"><span class="listing-state state-{{ $listing->effectiveStatus() }}">{{ __('market.status_'.$listing->effectiveStatus()) }}</span>@if(in_array($listing->effectiveStatus(),['active','reserved'],true))<small>{{ __('market.expires',['date'=>$listing->expires_at->timezone(config('app.display_timezone'))->translatedFormat('j M Y')]) }}</small>@endif</div>
<div class="manage-listing-actions">
@if($listing->status === 'draft')<a class="button secondary" href="{{ route('drafts.edit',['listing'=>$listing,'step'=>$listing->last_step]) }}">{{ __('listing.resume') }}</a>
@elseif($publicIds->contains($listing->id))<a class="button secondary" href="{{ route('listings.show',$listing) }}">{{ __('market.view') }}</a>@elseif(in_array($listing->effectiveStatus(),['active','reserved'],true))<p class="field-hint">{{ __('market.hidden_public') }}</p>@endif
@if($actions = $lifecycle->actions($listing))<details class="listing-action-menu"><summary aria-label="{{ __('market.actions') }} — {{ $listing->title ?: __('listing.untitled') }}">{{ __('market.actions') }} <span aria-hidden="true">⌄</span></summary><div>@foreach($actions as $action)<a href="{{ route('listings.confirm',['listing'=>$listing,'action'=>$action]) }}">{{ __('market.action_'.$action) }}</a>@endforeach</div></details>@endif
</div>
</article>
@endforeach</div>
{{ $listings->links('components.pagination') }}
@endif
</section>
@endsection
