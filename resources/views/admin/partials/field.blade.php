{{-- One labelled control with its hint and error. $name may be dotted (settings.primary); ids follow it. --}}
@php
    $id = 'f-' . str_replace('.', '-', $name);
    $inputName = str_contains($name, '.') ? preg_replace('/\.([^.]+)/', '[$1]', $name) : $name;
    $value = old($name, $value ?? '');
    $type = $type ?? 'text';
    $describedBy = trim((isset($hint) ? $id . '-hint ' : '') . ($errors->has($name) ? $id . '-error' : ''));
@endphp
<div class="wka-field @if ($type === 'checkbox') wka-field-check @endif">
    @if ($type === 'checkbox')
        <input type="hidden" name="{{ $inputName }}" value="0">
        <input id="{{ $id }}" type="checkbox" name="{{ $inputName }}" value="1" @checked((bool) $value) @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
        <label for="{{ $id }}">{{ $label }}</label>
    @else
        <label for="{{ $id }}">{{ $label }}@if ($required ?? false) <span aria-hidden="true">*</span>@endif</label>
        @if ($type === 'textarea')
            <textarea id="{{ $id }}" name="{{ $inputName }}" rows="{{ $rows ?? 4 }}" @if ($required ?? false) required @endif @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @error($name) aria-invalid="true" @enderror>{{ $value }}</textarea>
        @elseif ($type === 'select')
            <select id="{{ $id }}" name="{{ $inputName }}" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @error($name) aria-invalid="true" @enderror>
                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @else
            <input id="{{ $id }}" type="{{ $type }}" name="{{ $inputName }}" value="{{ $value }}" @if ($required ?? false) required @endif
                   @isset($maxlength) maxlength="{{ $maxlength }}" @endisset @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @error($name) aria-invalid="true" @enderror>
        @endif
    @endif
    @isset($hint)<p class="wka-hint" id="{{ $id }}-hint">{{ $hint }}</p>@endisset
    @error($name)<p class="wka-error" id="{{ $id }}-error">{{ $message }}</p>@enderror
</div>
