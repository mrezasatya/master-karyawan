@props(['name', 'label' => null, 'type' => 'text', 'value' => null, 'form' => null, 'required' => false, 'help' => null])
@php
    $id = ($form ? $form.'-' : '').$name;
    $current = form_value($form, $name, $value);
    $error = form_error($form, $name);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }} @if ($required)<span class="text-red-600">*</span>@endif</label>
    @endif
    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" {{ $attributes->except('class')->merge(['class' => 'input '.($error ? 'input-error' : ''), 'rows' => 3]) }} @if ($required) required @endif>{{ $current }}</textarea>
    @else
        <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" value="{{ $current }}" {{ $attributes->except('class')->merge(['class' => 'input '.($error ? 'input-error' : '')]) }} @if ($required) required @endif>
    @endif
    @if ($error)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @elseif ($help)
        <p class="mt-1 text-xs text-slate-500">{{ $help }}</p>
    @endif
</div>
