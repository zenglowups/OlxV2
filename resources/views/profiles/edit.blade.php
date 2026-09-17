@extends('layouts.app')
@section('title', __('profile.edit'))
@section('content')
<section class="profile-workspace container">
    <header class="page-heading"><p class="eyebrow">{{ __('profile.edit') }}</p><h1>{{ __('profile.title') }}</h1><p>{{ __('profile.intro') }}</p></header>
    <div class="profile-editor">
        <aside class="profile-sidebar">
            @if($profile->avatar_id)<img class="profile-avatar" src="{{ route('avatar', ['avatar' => $profile->avatar_id]) }}" alt="{{ __('profile.photo_alt', ['name' => auth()->user()->name]) }}" width="128" height="128">@endif
            <h2>{{ auth()->user()->name }}</h2>
            <p>{{ $profile->is_public ? __('profile.visible') : __('profile.hidden') }}</p>
            @if($profile->exists)<a class="text-link" href="{{ route('profile.preview') }}">{{ __('profile.preview') }} →</a>@endif
            @if($profile->is_public)<a class="text-link" href="{{ route('profile.show', ['profile' => $profile->username]) }}">{{ __('profile.view_public') }} →</a>@endif
            <a class="text-link" href="{{ route('account') }}">{{ __('profile.back_account') }}</a>
        </aside>
        <div class="form-card">
            <x-form-feedback />
            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
                <fieldset><legend>{{ __('profile.public_details') }}</legend>
                    <x-form-field name="name" :label="__('auth.name')" :value="auth()->user()->name" required minlength="2" maxlength="80" autocomplete="nickname" />
                    <x-form-field name="username" :label="__('profile.username')" :value="$profile->username" :hint="__('profile.username_hint')" required minlength="3" maxlength="32" autocapitalize="none" autocomplete="username" />
                    <div class="form-field"><label for="description">{{ __('profile.description') }}</label><textarea name="description" id="description" rows="5" maxlength="1500" aria-describedby="description-help" @if($errors->has('description')) aria-invalid="true" @endif>{{ is_string(old('description', $profile->description)) ? old('description', $profile->description) : '' }}</textarea><p id="description-help" class="field-hint">{{ $errors->first('description') ?: __('profile.description_hint') }}</p></div>
                    <x-form-field name="location_label" :label="__('profile.location')" :value="$profile->location_label" :hint="__('profile.location_hint')" maxlength="100" autocomplete="address-level2" />
                </fieldset>
                <fieldset><legend>{{ __('profile.business_details') }}</legend>
                    <div class="form-field"><label for="account_type">{{ __('auth.account_type') }}</label><select id="account_type" name="account_type" data-account-type>
                        <option value="individual" @selected(old('account_type', auth()->user()->account_type) === 'individual')>{{ __('auth.individual') }}</option>
                        <option value="business" @selected(old('account_type', auth()->user()->account_type) === 'business')>{{ __('auth.business') }}</option>
                    </select></div>
                    <div class="business-fields" data-business-fields>
                        <x-form-field name="business_name" :label="__('profile.business_name')" :value="$profile->business_name" maxlength="160" autocomplete="organization" />
                        <x-form-field name="tax_id" :label="__('profile.tax_id')" :value="$profile->tax_id" :hint="__('profile.tax_hint')" maxlength="12" />
                        <label class="checkbox-field"><input type="checkbox" name="show_tax_id" value="1" @checked(old('show_tax_id', $profile->show_tax_id))>{{ __('profile.show_tax_id') }}</label>
                    </div>
                </fieldset>
                <fieldset><legend>{{ __('profile.avatar') }}</legend>
                    <div class="form-field"><label for="avatar">{{ __('profile.avatar') }}</label><input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="avatar-help"><p id="avatar-help" class="{{ $errors->has('avatar') ? 'field-error' : 'field-hint' }}">{{ $errors->first('avatar') ?: __('profile.avatar_hint') }}</p></div>
                    @if($profile->avatar_id)<label class="checkbox-field"><input type="checkbox" name="remove_avatar" value="1">{{ __('profile.remove_avatar') }}</label>@endif
                </fieldset>
                <fieldset><legend>{{ __('profile.private_contact') }}</legend>
                    <p class="field-hint">{{ __('profile.contact_hint') }}</p>
                    <x-form-field name="phone" :label="__('profile.phone')" :value="$profile->phone" type="tel" :hint="__('profile.phone_hint')" maxlength="24" autocomplete="tel" />
                    <div class="form-field"><label for="contact_method">{{ __('profile.contact_method') }}</label><select name="contact_method" id="contact_method">
                        @foreach(['email','phone'] as $method)<option value="{{ $method }}" @selected(old('contact_method', $profile->contact_method) === $method)>{{ __('profile.'.($method === 'phone' ? 'phone_option' : $method)) }}</option>@endforeach
                    </select></div>
                </fieldset>
                <fieldset><legend>{{ __('profile.visibility') }}</legend>
                    <label class="checkbox-field"><input type="checkbox" name="is_public" value="1" @checked(old('is_public', $profile->is_public)) aria-describedby="visibility-help">{{ __('profile.is_public') }}</label><p id="visibility-help" class="field-hint">{{ __('profile.visibility_hint') }}</p>
                </fieldset>
                <button class="button" type="submit">{{ __('profile.save') }}</button>
            </form>
        </div>
    </div>
</section>
@endsection
