<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('laranail/waitlist-kit::admin.login') }} · {{ __('laranail/waitlist-kit::admin.name') }}</title>
    {{ $adminAssets }}
</head>
<body class="wka">
<main id="content" class="wka-main wka-narrow">
    <h1>{{ __('laranail/waitlist-kit::admin.login') }}</h1>
    @include('laranail/waitlist-kit::admin.partials.errors')
    <form method="post" action="{{ route('laranail-waitlist-kit.admin.login.store') }}" class="wka-form">
        @csrf
        @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'email', 'type' => 'email', 'label' => __('laranail/waitlist-kit::admin.email'), 'required' => true])
        @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'password', 'type' => 'password', 'label' => __('laranail/waitlist-kit::admin.password'), 'required' => true, 'value' => ''])
        @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'remember', 'type' => 'checkbox', 'label' => __('laranail/waitlist-kit::admin.remember')])
        <button type="submit" class="wka-button">{{ __('laranail/waitlist-kit::admin.login') }}</button>
    </form>
</main>
</body>
</html>
