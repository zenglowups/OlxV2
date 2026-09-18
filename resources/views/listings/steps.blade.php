<nav class="wizard-steps" aria-label="{{ __('listing.progress') }}"><ol>
@foreach($steps as $item)
<li><a href="{{ $item === 'preview' ? route('drafts.preview', $listing) : route('drafts.edit', ['listing' => $listing, 'step' => $item]) }}" @if($item === $step) aria-current="step" @endif><span class="step-number" aria-hidden="true">{{ $loop->iteration }}</span><span>{{ __('listing.step_'.$item) }}</span></a></li>
@endforeach
</ol></nav>