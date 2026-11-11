@extends('laranail/waitlist-kit::admin.layout', ['title' => $campaign->name])

@section('content')
    <p>
        @if ($campaign->acceptsSignups())
            <a href="{{ route('laranail-waitlist-kit.campaign.show', $campaign->slug) }}">{{ __('laranail/waitlist-kit::admin.view_page') }}</a> ·
        @endif
        <a href="{{ route('laranail-waitlist-kit.admin.subscribers.index', $campaign) }}">{{ __('laranail/waitlist-kit::admin.subscribers') }}</a>
    </p>

    <section class="wka-panel" aria-labelledby="details">
        <h2 id="details">{{ __('laranail/waitlist-kit::admin.details') }}</h2>
        <form method="post" action="{{ route('laranail-waitlist-kit.admin.campaigns.update', $campaign) }}" class="wka-form">
            @csrf @method('PUT')
            @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'name', 'label' => __('laranail/waitlist-kit::admin.name_field'), 'value' => $campaign->name, 'required' => true, 'maxlength' => 120])
            @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'slug', 'label' => __('laranail/waitlist-kit::admin.slug'), 'value' => $campaign->slug, 'required' => true, 'maxlength' => 120, 'hint' => __('laranail/waitlist-kit::admin.slug_hint')])
            @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'type', 'type' => 'select', 'label' => __('laranail/waitlist-kit::admin.type'), 'value' => $campaign->type->value, 'options' => collect($types)->mapWithKeys(fn ($t) => [$t->value => $t->value])->all()])
            @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'status', 'type' => 'select', 'label' => __('laranail/waitlist-kit::admin.status'), 'value' => $campaign->status->value, 'options' => collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->value])->all(), 'hint' => __('laranail/waitlist-kit::admin.status_hint')])
            @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'locales', 'label' => __('laranail/waitlist-kit::admin.locales'), 'value' => implode(', ', $campaign->locales), 'hint' => __('laranail/waitlist-kit::admin.locales_hint')])
            @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'launches_at', 'type' => 'datetime-local', 'label' => __('laranail/waitlist-kit::admin.launches_at'), 'value' => $campaign->launches_at?->format('Y-m-d\TH:i')])
            @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'host', 'label' => __('laranail/waitlist-kit::admin.host'), 'value' => $campaign->host, 'hint' => __('laranail/waitlist-kit::admin.host_hint')])
            <button type="submit" class="wka-button">{{ __('laranail/waitlist-kit::admin.save') }}</button>
        </form>
    </section>

    <section class="wka-panel" id="theme" aria-labelledby="theme-title">
        <h2 id="theme-title">{{ __('laranail/waitlist-kit::admin.theme') }}</h2>
        <form method="post" action="{{ route('laranail-waitlist-kit.admin.campaigns.theme', $campaign) }}" class="wka-form">
            @csrf @method('PUT')
            @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'theme', 'type' => 'select', 'label' => __('laranail/waitlist-kit::admin.theme'), 'value' => $theme->name, 'options' => collect($themes)->mapWithKeys(fn ($t) => [$t->name => $t->label . ' (' . $t->styling->value . ')'])->all()])
            @foreach ($theme->settings as $setting)
                @include('laranail/waitlist-kit::admin.partials.field', [
                    'name'  => 'settings.' . $setting->key,
                    'label' => $setting->label !== '' ? $setting->label : $setting->key,
                    'type'  => match ($setting->type->value) { 'color' => 'color', 'toggle' => 'checkbox', default => 'text' },
                    'value' => $campaign->theme_settings[$setting->key] ?? $setting->default,
                ])
            @endforeach
            <p class="wka-hint">{{ __('laranail/waitlist-kit::admin.theme_hint') }}</p>
            <button type="submit" class="wka-button">{{ __('laranail/waitlist-kit::admin.save') }}</button>
        </form>
    </section>

    @include('laranail/waitlist-kit::admin.partials.blocks', ['owner' => $campaign, 'store' => route('laranail-waitlist-kit.admin.campaigns.blocks.store', $campaign), 'locales' => $campaign->locales])

    @can(\Simtabi\Laranail\WaitlistKit\Auth\Abilities::ERASE)
        <section class="wka-panel wka-danger" aria-labelledby="delete">
            <h2 id="delete">{{ __('laranail/waitlist-kit::admin.delete_campaign') }}</h2>
            <p>{{ __('laranail/waitlist-kit::admin.delete_warning') }}</p>
            <form method="post" action="{{ route('laranail-waitlist-kit.admin.campaigns.destroy', $campaign) }}" class="wka-form">
                @csrf @method('DELETE')
                @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'confirm', 'label' => __('laranail/waitlist-kit::admin.type_slug', ['slug' => $campaign->slug]), 'value' => ''])
                <button type="submit" class="wka-button wka-button-danger">{{ __('laranail/waitlist-kit::admin.delete') }}</button>
            </form>
        </section>
    @endcan
@endsection
