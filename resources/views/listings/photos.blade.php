@extends('layouts.app')
@section('title', __('photo.title'))
@section('content')
<section class="container sell-page">
<div class="wizard-topline"><a href="{{ route('drafts.index') }}">← {{ __('listing.exit') }}</a><span class="private-badge"><x-icon name="shield"/>{{ __('listing.draft') }}</span></div>
@include('listings.steps')
<div class="wizard-layout">
<div class="wizard-card photos-workspace" data-photos @if($photos->contains(fn($photo) => in_array($photo->status, ['uploading', 'queued'], true))) data-photo-status="{{ route('photos.status', $listing) }}" @endif>
<header><p class="eyebrow">{{ $listing->category->label() }}</p><h1>{{ __('photo.title') }}</h1><p>{{ __('photo.intro') }}</p></header>
<x-form-feedback />
<div class="photo-count"><strong>{{ __('photo.count', ['count' => $photos->count()]) }}</strong><span>{{ __('photo.private') }}</span></div>
@if($photos->isNotEmpty())
<div class="photo-grid">
@foreach($photos as $photo)
<article class="photo-item" data-photo-id="{{ $photo->public_id }}" data-photo-state="{{ $photo->status }}">
<div class="photo-thumbnail">
@if($photo->status === 'ready')
<img src="{{ $photo->imageUrl('thumb') }}" width="{{ $photo->variants['thumb']['width'] }}" height="{{ $photo->variants['thumb']['height'] }}" alt="{{ __('photo.number', ['number' => $loop->iteration]) }}" loading="lazy" decoding="async">
@else
<span class="photo-processing @if($photo->status === 'failed') photo-failed @endif"><x-icon name="{{ $photo->status === 'failed' ? 'shield' : 'calendar' }}"/><span>{{ $photo->status === 'failed' ? __('photo.failed') : __('photo.processing') }}</span>@if($photo->status === 'failed')<small>{{ __('photo.failed_hint') }}</small>@endif</span>
@endif
@if($loop->first)<span class="photo-primary">{{ __('photo.primary') }}</span>@endif
</div>
<div class="photo-tools">
<form method="POST" action="{{ route('photos.update', ['listing' => $listing, 'photo' => $photo]) }}">@csrf @method('PUT')<input type="hidden" name="version" value="{{ $listing->version }}">
@if(!$loop->first)<button name="action" value="left" type="submit" class="photo-tool" aria-label="{{ __('photo.left') }} — {{ __('photo.number', ['number' => $loop->iteration]) }}">←</button>@endif
@if(!$loop->last)<button name="action" value="right" type="submit" class="photo-tool" aria-label="{{ __('photo.right') }} — {{ __('photo.number', ['number' => $loop->iteration]) }}">→</button>@endif
@if(!$loop->first)<button name="action" value="primary" type="submit" class="photo-tool photo-make-primary" aria-label="{{ __('photo.make_primary') }} — {{ __('photo.number', ['number' => $loop->iteration]) }}">{{ __('photo.make_primary') }}</button>@endif
</form>
<form method="POST" action="{{ route('photos.destroy', ['listing' => $listing, 'photo' => $photo]) }}">@csrf @method('DELETE')<input type="hidden" name="version" value="{{ $listing->version }}"><button type="submit" class="photo-tool photo-delete" aria-label="{{ __('photo.remove') }} — {{ __('photo.number', ['number' => $loop->iteration]) }}">×</button></form>
</div>
</article>
@endforeach
</div>
@endif
@if($photos->count() < 8)
<form method="POST" enctype="multipart/form-data" action="{{ route('photos.store', $listing) }}" class="photo-upload" data-photo-upload data-limit="{{ 8 - $photos->count() }}" data-invalid="{{ __('photo.file_limit') }}" data-leave="{{ __('photo.saved_selection') }}">@csrf
<input type="hidden" name="version" value="{{ $listing->version }}">
@if($photos->isEmpty())<span class="upload-symbol"><x-icon name="camera"/></span><h2>{{ __('photo.empty') }}</h2><p>{{ __('photo.empty_hint') }}</p>@endif
<label for="photos">{{ __('photo.choose') }}</label><input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required aria-describedby="photos-hint"><p id="photos-hint" class="field-hint">{{ __('photo.hint') }}</p><p data-upload-error class="field-error" role="alert" hidden></p><button class="button secondary" type="submit" data-upload-label="{{ __('photo.uploading') }}"><span aria-hidden="true">+</span>{{ __('photo.upload') }}</button>
</form>
@endif
<p class="photo-refresh"><a href="{{ url()->current() }}">{{ __('photo.refresh') }}</a><span>{{ __('photo.auto_refresh') }}</span></p>
<p class="field-hint">{{ __('photo.optional') }}</p>
<form method="POST" action="{{ route('drafts.update', ['listing' => $listing, 'step' => 'photos']) }}">@csrf<input type="hidden" name="version" value="{{ $listing->version }}"><div class="wizard-actions"><a class="button secondary" href="{{ route('drafts.edit', ['listing' => $listing, 'step' => 'category']) }}">← {{ __('listing.back') }}</a><button class="button button-primary" name="intent" value="next" type="submit">{{ __('listing.next') }} →</button></div><button class="text-button save-close" name="intent" value="exit" type="submit">{{ __('listing.save_exit') }}</button></form>
</div>
<aside class="wizard-advice"><span class="advice-symbol"><x-icon name="camera"/></span><p class="eyebrow">{{ config('app.name') }}</p><h2>{{ __('listing.tips_title') }}</h2><p>{{ __('photo.tip') }}</p><div class="advice-privacy"><x-icon name="shield"/><span>{{ __('photo.private') }}</span></div></aside>
</div>
</section>
@endsection
