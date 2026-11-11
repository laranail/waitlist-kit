@extends('laranail/waitlist-kit::admin.layout', ['title' => $integration ? __('laranail/waitlist-kit::admin.edit_integration') : __('laranail/waitlist-kit::admin.new_integration')])

@section('content')
    @php($driver = old('driver', $integration?->driver ?? 'app'))
    <form method="post" action="{{ $integration ? route('laranail-waitlist-kit.admin.integrations.update', $integration) : route('laranail-waitlist-kit.admin.integrations.store') }}" class="wka-form">
        @csrf
        @if ($integration) @method('PUT') @endif
        @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'driver', 'type' => 'select', 'label' => __('laranail/waitlist-kit::admin.driver'), 'value' => $driver, 'options' => array_combine($drivers, $drivers)])
        @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'campaign', 'type' => 'select', 'label' => __('laranail/waitlist-kit::admin.serves'), 'value' => $integration?->integrable?->slug ?? '', 'options' => ['' => __('laranail/waitlist-kit::admin.every_campaign')] + $campaigns->pluck('name', 'slug')->all()])
        <fieldset>
            <legend>{{ __('laranail/waitlist-kit::admin.settings_for', ['driver' => 'app']) }}</legend>
            @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'endpoint', 'type' => 'url', 'label' => __('laranail/waitlist-kit::admin.endpoint'), 'value' => $integration?->driver === 'app' ? ($integration->settings['endpoint'] ?? '') : '', 'hint' => __('laranail/waitlist-kit::admin.endpoint_hint')])
        </fieldset>
        <fieldset>
            <legend>{{ __('laranail/waitlist-kit::admin.settings_for', ['driver' => 'brevo']) }}</legend>
            @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'api_key', 'type' => 'password', 'label' => __('laranail/waitlist-kit::admin.api_key'), 'value' => '', 'hint' => $integration ? __('laranail/waitlist-kit::admin.secret_kept') : null])
            @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'list_id', 'type' => 'number', 'label' => __('laranail/waitlist-kit::admin.list_id'), 'value' => $integration?->driver === 'brevo' ? ($integration->settings['list_id'] ?? '') : ''])
        </fieldset>
        @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'enabled', 'type' => 'checkbox', 'label' => __('laranail/waitlist-kit::admin.enabled'), 'value' => $integration?->enabled ?? true])
        <button type="submit" class="wka-button">{{ __('laranail/waitlist-kit::admin.save') }}</button>
    </form>
@endsection
