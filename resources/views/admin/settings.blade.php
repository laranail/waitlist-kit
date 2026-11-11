@extends('laranail/waitlist-kit::admin.layout', ['title' => __('laranail/waitlist-kit::admin.nav.settings'), 'pretitle' => __('laranail/waitlist-kit::admin.manage')])

@section('content')
    <div class="alert alert-info" role="status">{{ $writable ? __('laranail/waitlist-kit::admin.settings_writable') : __('laranail/waitlist-kit::admin.settings_readonly') }}</div>

    @if ($writable)
        <div class="row">
            <div class="col-lg-8">
                <form method="post" action="{{ route('laranail-waitlist-kit.admin.settings.update') }}" class="card">
                    @csrf @method('PUT')
                    <div class="card-body">
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
                    </div>
                    <div class="card-footer text-end"><button type="submit" class="btn btn-primary">{{ __('laranail/waitlist-kit::admin.save') }}</button></div>
                </form>
            </div>
        </div>
    @else
        <div class="card">
            <div class="table-responsive" tabindex="0" role="region" aria-labelledby="wka-settings-table">
                <table class="table table-vcenter card-table">
                    <caption class="visually-hidden" id="wka-settings-table">{{ __('laranail/waitlist-kit::admin.nav.settings') }}</caption>
                    <thead><tr>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.setting') }}</th>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.value') }}</th>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.set_by') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($settings as $key => $setting)
                        <tr>
                            <th scope="row" class="fw-normal">{{ __('laranail/waitlist-kit::admin.settings.' . str_replace('.', '_', $key)) }}</th>
                            <td>{{ is_bool($values[$key]) ? ($values[$key] ? __('laranail/waitlist-kit::admin.yes') : __('laranail/waitlist-kit::admin.no')) : ($values[$key] !== '' ? $values[$key] : '-') }}</td>
                            <td><code>{{ $setting['env'] }}</code></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
