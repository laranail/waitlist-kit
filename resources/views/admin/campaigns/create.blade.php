@extends('laranail/waitlist-kit::admin.layout', ['title' => __('laranail/waitlist-kit::admin.new_campaign')])

@section('content')
    <form method="post" action="{{ route('laranail-waitlist-kit.admin.campaigns.store') }}" class="wka-form">
        @csrf
        @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'name', 'label' => __('laranail/waitlist-kit::admin.name_field'), 'required' => true, 'maxlength' => 120])
        @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'slug', 'label' => __('laranail/waitlist-kit::admin.slug'), 'required' => true, 'maxlength' => 120, 'hint' => __('laranail/waitlist-kit::admin.slug_hint')])
        @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'type', 'type' => 'select', 'label' => __('laranail/waitlist-kit::admin.type'), 'options' => collect($types)->mapWithKeys(fn ($t) => [$t->value => $t->value])->all()])
        @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'locales', 'label' => __('laranail/waitlist-kit::admin.locales'), 'value' => old('locales', 'en'), 'hint' => __('laranail/waitlist-kit::admin.locales_hint')])
        <button type="submit" class="wka-button">{{ __('laranail/waitlist-kit::admin.create') }}</button>
    </form>
@endsection
