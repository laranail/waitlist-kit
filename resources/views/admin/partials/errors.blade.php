@if ($errors->any())
    <div class="wka-errors" role="alert" aria-labelledby="wka-errors-title" tabindex="-1">
        <h2 id="wka-errors-title">{{ __('laranail/waitlist-kit::admin.errors') }}</h2>
        <ul>
            @foreach ($errors->keys() as $key)
                <li><a href="#{{ 'f-' . str_replace('.', '-', $key) }}">{{ $errors->first($key) }}</a></li>
            @endforeach
        </ul>
    </div>
@endif
