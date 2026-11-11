@if ($errors->any())
    {{-- Link to the control in the form that was submitted: a block's form, the add-block form, or the page's own. --}}
    @php($idPrefix = \Simtabi\Laranail\WaitlistKit\Support\AdminForm::submitted(old('block'), old('_form')))
    <div class="alert alert-danger" role="alert" aria-labelledby="wka-errors-title" tabindex="-1">
        <div class="d-flex gap-2">
            @include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'alert'])
            <div>
                <h2 class="alert-heading h4" id="wka-errors-title">{{ __('laranail/waitlist-kit::admin.errors') }}</h2>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->keys() as $key)
                        <li><a href="#{{ \Simtabi\Laranail\WaitlistKit\Support\AdminForm::id($idPrefix, $key) }}" class="alert-link">{{ $errors->first($key) }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
