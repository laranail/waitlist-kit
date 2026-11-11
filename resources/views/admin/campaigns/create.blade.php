@extends('laranail/waitlist-kit::admin.layout', ['title' => __('laranail/waitlist-kit::admin.new_campaign'), 'pretitle' => __('laranail/waitlist-kit::admin.nav.campaigns')])

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <form method="post" action="{{ route('laranail-waitlist-kit.admin.campaigns.store') }}" class="card">
                @csrf
                <div class="card-body">
                    @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'name', 'label' => __('laranail/waitlist-kit::admin.name_field'), 'required' => true, 'maxlength' => 120])
                    @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'slug', 'label' => __('laranail/waitlist-kit::admin.slug'), 'required' => true, 'maxlength' => 120, 'hint' => __('laranail/waitlist-kit::admin.slug_hint')])
                    @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'type', 'type' => 'select', 'label' => __('laranail/waitlist-kit::admin.type'), 'options' => collect($types)->mapWithKeys(fn ($t) => [$t->value => str_replace('_', ' ', ucfirst($t->value))])->all()])
                    @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'locales', 'label' => __('laranail/waitlist-kit::admin.locales'), 'value' => old('locales', 'en'), 'hint' => __('laranail/waitlist-kit::admin.locales_hint')])
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">{{ __('laranail/waitlist-kit::admin.create') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
