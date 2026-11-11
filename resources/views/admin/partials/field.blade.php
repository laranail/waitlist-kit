{{-- One labelled control with its hint and error. $name may be dotted (settings.primary); ids follow it.
     $idPrefix keeps ids unique when one page holds several forms with the same fields (the block editor). --}}
@php
    $idPrefix ??= \Simtabi\Laranail\WaitlistKit\Support\AdminForm::PAGE;
    $id = \Simtabi\Laranail\WaitlistKit\Support\AdminForm::id($idPrefix, $name);
    $inputName = str_contains($name, '.') ? preg_replace('/\.([^.]+)/', '[$1]', $name) : $name;
    // Only the submitted form's fields take back its input and show its errors: the block editor repeats field
    // names across forms, so without this one block's rejected input and errors appeared in every block.
    $isSubmitted = \Simtabi\Laranail\WaitlistKit\Support\AdminForm::submitted(old('block'), old('_form')) === $idPrefix;
    $value = $isSubmitted ? old($name, $value ?? '') : ($value ?? '');
    $type = $type ?? 'text';
    $error = $isSubmitted ? $errors->first($name) : '';
    $describedBy = trim((isset($hint) ? $id . '-hint ' : '') . ($error !== '' ? $id . '-error' : ''));
@endphp
<div class="wka-field @if ($type === 'checkbox') wka-field-check @endif">
    @if ($type === 'checkbox')
        <input type="hidden" name="{{ $inputName }}" value="0">
        <input id="{{ $id }}" type="checkbox" name="{{ $inputName }}" value="1" @checked((bool) $value) @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif>
        <label for="{{ $id }}">{{ $label }}</label>
    @else
        <label for="{{ $id }}">{{ $label }}@if ($required ?? false) <span aria-hidden="true">*</span>@endif</label>
        @if ($type === 'textarea')
            <textarea id="{{ $id }}" name="{{ $inputName }}" rows="{{ $rows ?? 4 }}" @if ($required ?? false) required @endif @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error !== '') aria-invalid="true" @endif>{{ $value }}</textarea>
        @elseif ($type === 'select')
            <select id="{{ $id }}" name="{{ $inputName }}" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error !== '') aria-invalid="true" @endif>
                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @else
            <input id="{{ $id }}" type="{{ $type }}" name="{{ $inputName }}" value="{{ $value }}" @if ($required ?? false) required @endif
                   @isset($maxlength) maxlength="{{ $maxlength }}" @endisset @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error !== '') aria-invalid="true" @endif>
        @endif
    @endif
    @isset($hint)<p class="wka-hint" id="{{ $id }}-hint">{{ $hint }}</p>@endisset
    @if ($error !== '')<p class="wka-error" id="{{ $id }}-error">{{ $error }}</p>@endif
</div>
