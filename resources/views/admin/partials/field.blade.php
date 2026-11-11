{{-- One labelled control with its hint and error, in Tabler's form markup. $name may be dotted (settings.primary); ids
     follow it. $wrapperClass replaces the default `mb-3` (an inline filter form passes `col-auto`).
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
    $invalid = $error !== '' ? ' is-invalid' : '';
    $describedBy = trim((isset($hint) ? $id . '-hint ' : '') . ($error !== '' ? $id . '-error' : ''));
@endphp
<div class="{{ $wrapperClass ?? 'mb-3' }}">
    @if ($type === 'checkbox')
        <input type="hidden" name="{{ $inputName }}" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input{{ $invalid }}" id="{{ $id }}" type="checkbox" name="{{ $inputName }}" value="1" @checked((bool) $value) @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error !== '') aria-invalid="true" @endif>
            <label class="form-check-label" for="{{ $id }}">{{ $label }}</label>
        </div>
    @else
        <label class="form-label" for="{{ $id }}">{{ $label }}@if ($required ?? false) <span class="text-danger" aria-hidden="true">*</span>@endif</label>
        @if ($type === 'textarea')
            <textarea class="form-control{{ $invalid }}" id="{{ $id }}" name="{{ $inputName }}" rows="{{ $rows ?? 4 }}" @if ($required ?? false) required @endif @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error !== '') aria-invalid="true" @endif>{{ $value }}</textarea>
        @elseif ($type === 'select')
            <select class="form-select{{ $invalid }}" id="{{ $id }}" name="{{ $inputName }}" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error !== '') aria-invalid="true" @endif>
                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @else
            <input class="form-control{{ $type === 'color' ? ' form-control-color' : '' }}{{ $invalid }}" id="{{ $id }}" type="{{ $type }}" name="{{ $inputName }}" value="{{ $value }}" @if ($required ?? false) required @endif
                   @isset($maxlength) maxlength="{{ $maxlength }}" @endisset @isset($autocomplete) autocomplete="{{ $autocomplete }}" @endisset @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error !== '') aria-invalid="true" @endif>
        @endif
    @endif
    @isset($hint)<p class="form-hint mt-1 mb-0" id="{{ $id }}-hint">{{ $hint }}</p>@endisset
    @if ($error !== '')<p class="invalid-feedback d-block mb-0" id="{{ $id }}-error">{{ $error }}</p>@endif
</div>
