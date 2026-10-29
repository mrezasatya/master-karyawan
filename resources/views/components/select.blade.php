@props(['name', 'label' => null, 'options' => [], 'value' => null, 'form' => null, 'required' => false, 'placeholder' => '— Pilih —', 'help' => null])
@php
    $id = ($form ? $form.'-' : '').$name;
    $current = (string) form_value($form, $name, $value);
    $error = form_error($form, $name);
    $isList = array_is_list($options);
@endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="label">{{ $label }} @if ($required)<span class="text-red-600">*</span>@endif</label>
    @endif
    <select id="{{ $id }}" name="{{ $name }}" {{ $attributes->except('class')->merge(['class' => 'input '.($error ? 'input-error' : '')]) }} @if ($required) required @endif>
        @if ($placeholder !== false)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $key => $text)
            @php $optValue = (string) ($isList ? $text : $key); @endphp
            <option value="{{ $optValue }}" @selected($current === $optValue)>{{ $text }}</option>
        @endforeach
        @if ($current !== '' && ! in_array($current, array_map('strval', $isList ? $options : array_keys($options)), true))
            <option value="{{ $current }}" selected>{{ $current }} (nilai lama)</option>
        @endif
    </select>
    @if ($error)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @elseif ($help)
        <p class="mt-1 text-xs text-slate-500">{{ $help }}</p>
    @endif
</div>
