@extends('laranail/waitlist-kit::admin.layout', ['title' => $campaign->name, 'pretitle' => __('laranail/waitlist-kit::admin.nav.campaigns')])

@section('actions')
    @if ($campaign->acceptsSignups())
        <a class="btn btn-outline-secondary" href="{{ route('laranail-waitlist-kit.campaign.show', $campaign->slug) }}">@include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'external']) {{ __('laranail/waitlist-kit::admin.view_page') }}</a>
    @endif
    <a class="btn btn-outline-secondary" href="{{ route('laranail-waitlist-kit.admin.subscribers.index', $campaign) }}">@include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'subscribers']) {{ __('laranail/waitlist-kit::admin.subscribers') }}</a>
@endsection

@section('content')
    <div class="row row-cards">
        <div class="col-lg-7">
            <form method="post" action="{{ route('laranail-waitlist-kit.admin.campaigns.update', $campaign) }}" class="card" aria-labelledby="details">
                @csrf @method('PUT')
                <div class="card-header"><h2 class="card-title" id="details">{{ __('laranail/waitlist-kit::admin.details') }}</h2></div>
                <div class="card-body">
                    @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'name', 'label' => __('laranail/waitlist-kit::admin.name_field'), 'value' => $campaign->name, 'required' => true, 'maxlength' => 120])
                    @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'slug', 'label' => __('laranail/waitlist-kit::admin.slug'), 'value' => $campaign->slug, 'required' => true, 'maxlength' => 120, 'hint' => __('laranail/waitlist-kit::admin.slug_hint')])
                    <div class="row">
                        <div class="col-md-6">
                            @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'type', 'type' => 'select', 'label' => __('laranail/waitlist-kit::admin.type'), 'value' => $campaign->type->value, 'options' => collect($types)->mapWithKeys(fn ($t) => [$t->value => str_replace('_', ' ', ucfirst($t->value))])->all()])
                        </div>
                        <div class="col-md-6">
                            @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'status', 'type' => 'select', 'label' => __('laranail/waitlist-kit::admin.status'), 'value' => $campaign->status->value, 'options' => collect($statuses)->mapWithKeys(fn ($s) => [$s->value => __('laranail/waitlist-kit::admin.statuses.' . $s->value)])->all(), 'hint' => __('laranail/waitlist-kit::admin.status_hint')])
                        </div>
                    </div>
                    @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'locales', 'label' => __('laranail/waitlist-kit::admin.locales'), 'value' => implode(', ', $campaign->locales), 'hint' => __('laranail/waitlist-kit::admin.locales_hint')])
                    <div class="row">
                        <div class="col-md-6">
                            @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'launches_at', 'type' => 'datetime-local', 'label' => __('laranail/waitlist-kit::admin.launches_at'), 'value' => $campaign->launches_at?->format('Y-m-d\TH:i')])
                        </div>
                        <div class="col-md-6">
                            @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'host', 'label' => __('laranail/waitlist-kit::admin.host'), 'value' => $campaign->host, 'hint' => __('laranail/waitlist-kit::admin.host_hint')])
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">{{ __('laranail/waitlist-kit::admin.save') }}</button>
                </div>
            </form>
        </div>

        <div class="col-lg-5">
            <form method="post" action="{{ route('laranail-waitlist-kit.admin.campaigns.theme', $campaign) }}" class="card" id="theme" aria-labelledby="theme-title">
                @csrf @method('PUT')
                <div class="card-header"><h2 class="card-title" id="theme-title">{{ __('laranail/waitlist-kit::admin.theme') }}</h2></div>
                <div class="card-body">
                    @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'theme', 'type' => 'select', 'label' => __('laranail/waitlist-kit::admin.theme'), 'value' => $theme->name, 'options' => collect($themes)->mapWithKeys(fn ($t) => [$t->name => $t->label . ' (' . $t->styling->value . ')'])->all(), 'hint' => __('laranail/waitlist-kit::admin.theme_hint')])
                    @foreach ($theme->settings as $setting)
                        @include('laranail/waitlist-kit::admin.partials.field', [
                            'name'  => 'settings.' . $setting->key,
                            'label' => $setting->label !== '' ? $setting->label : $setting->key,
                            'type'  => match ($setting->type->value) { 'color' => 'color', 'toggle' => 'checkbox', default => 'text' },
                            'value' => $campaign->theme_settings[$setting->key] ?? $setting->default,
                        ])
                    @endforeach
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">{{ __('laranail/waitlist-kit::admin.save') }}</button>
                </div>
            </form>
        </div>

        <div class="col-12">
            @include('laranail/waitlist-kit::admin.partials.blocks', ['owner' => $campaign, 'store' => route('laranail-waitlist-kit.admin.campaigns.blocks.store', $campaign), 'locales' => $campaign->locales])
        </div>

        @can(\Simtabi\Laranail\WaitlistKit\Auth\Abilities::ERASE)
            <div class="col-12">
                <form method="post" action="{{ route('laranail-waitlist-kit.admin.campaigns.destroy', $campaign) }}" class="card" aria-labelledby="delete">
                    @csrf @method('DELETE')
                    <div class="card-status-top bg-danger"></div>
                    <div class="card-header"><h2 class="card-title" id="delete">{{ __('laranail/waitlist-kit::admin.delete_campaign') }}</h2></div>
                    <div class="card-body">
                        <p class="text-secondary">{{ __('laranail/waitlist-kit::admin.delete_warning') }}</p>
                        @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'confirm', 'label' => __('laranail/waitlist-kit::admin.type_slug', ['slug' => $campaign->slug]), 'value' => '', 'wrapperClass' => 'mb-0'])
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-danger">@include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'trash']) {{ __('laranail/waitlist-kit::admin.delete') }}</button>
                    </div>
                </form>
            </div>
        @endcan
    </div>
@endsection
