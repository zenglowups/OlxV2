@if(session('status'))
    <div class="notice success" role="status">{{ session('status') }}</div>
@endif
@if($errors->any())
    <div class="notice error" role="alert" tabindex="-1" id="form-errors">
        <h2>{{ __('auth.error_title') }}</h2>
        <ul>@foreach($errors->messages() as $field => $messages)<li><a href="#{{ $field }}">{{ $messages[0] }}</a></li>@endforeach</ul>
    </div>
@endif
