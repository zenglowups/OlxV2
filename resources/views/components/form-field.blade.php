@props(['name', 'label', 'type' => 'text', 'hint' => null])
@php($fieldValue = old($name, $attributes->get('value', '')))
<div class="form-field">
    <label for="{{ $name }}">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" {{ $attributes->except('value') }}
        @if($type !== 'password') value="{{ is_string($fieldValue) || is_int($fieldValue) || is_float($fieldValue) ? $fieldValue : '' }}" @endif
        @if($errors->has($name)) aria-invalid="true" @endif
        @if($hint || $errors->has($name)) aria-describedby="{{ $name }}-help" @endif>
    @if($hint || $errors->has($name))
        <p class="{{ $errors->has($name) ? 'field-error' : 'field-hint' }}" id="{{ $name }}-help">{{ $errors->first($name) ?: $hint }}</p>
    @endif
</div>
