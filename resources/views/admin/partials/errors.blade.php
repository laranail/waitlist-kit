@if ($errors->any())
    {{-- Link to the control in the form that was submitted: a block's form, the add-block form, or the page's own. --}}
    @php($idPrefix = \Simtabi\Laranail\WaitlistKit\Support\AdminForm::submitted(old('block'), old('_form')))
    <div class="wka-errors" role="alert" aria-labelledby="wka-errors-title" tabindex="-1">
        <h2 id="wka-errors-title">{{ __('laranail/waitlist-kit::admin.errors') }}</h2>
        <ul>
            @foreach ($errors->keys() as $key)
                <li><a href="#{{ \Simtabi\Laranail\WaitlistKit\Support\AdminForm::id($idPrefix, $key) }}">{{ $errors->first($key) }}</a></li>
            @endforeach
        </ul>
    </div>
@endif
