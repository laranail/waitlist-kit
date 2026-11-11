@extends('laranail/waitlist-kit::admin.layout', ['title' => __('laranail/waitlist-kit::admin.nav.settings')])

@section('content')
    @if ($writable)
        <p class="wka-hint">{{ __('laranail/waitlist-kit::admin.settings_writable') }}</p>
    @else
        <p class="wka-hint">{{ __('laranail/waitlist-kit::admin.settings_readonly') }}</p>
    @endif

    @if ($writable)
        <form method="post" action="{{ route('laranail-waitlist-kit.admin.settings.update') }}" class="wka-form">
            @csrf @method('PUT')
            @foreach ($settings as $key => $setting)
                @include('laranail/waitlist-kit::admin.partials.field', [
                    'name'    => $key,
                    'label'   => __('laranail/waitlist-kit::admin.settings.' . str_replace('.', '_', $key)),
                    'type'    => match ($setting['type']) { 'toggle' => 'checkbox', 'list' => 'text', default => $setting['type'] },
                    'value'   => $values[$key],
                    'options' => match ($key) {
                        'contact.company' => ['optional' => 'optional', 'required' => 'required', 'off' => 'off'],
                        'themes.default'  => $themes,
                        default           => [],
                    },
                    'hint'    => $setting['type'] === 'list' ? __('laranail/waitlist-kit::admin.list_hint') : null,
                ])
            @endforeach
            <button type="submit" class="wka-button">{{ __('laranail/waitlist-kit::admin.save') }}</button>
        </form>
    @else
        <table class="wka-table">
            <caption>{{ __('laranail/waitlist-kit::admin.nav.settings') }}</caption>
            <thead><tr>
                <th scope="col">{{ __('laranail/waitlist-kit::admin.setting') }}</th>
                <th scope="col">{{ __('laranail/waitlist-kit::admin.value') }}</th>
                <th scope="col">{{ __('laranail/waitlist-kit::admin.set_by') }}</th>
            </tr></thead>
            <tbody>
            @foreach ($settings as $key => $setting)
                <tr>
                    <th scope="row">{{ __('laranail/waitlist-kit::admin.settings.' . str_replace('.', '_', $key)) }}</th>
                    <td>{{ is_bool($values[$key]) ? ($values[$key] ? __('laranail/waitlist-kit::admin.yes') : __('laranail/waitlist-kit::admin.no')) : ($values[$key] !== '' ? $values[$key] : '-') }}</td>
                    <td><code>{{ $setting['env'] }}</code></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endsection
