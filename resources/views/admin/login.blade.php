{{-- The bundled sign-in page: Tabler's centred sign-in card. --}}
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="{{ $appearance->initialTheme() }}" data-wk-appearance="{{ $appearance->value }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('laranail/waitlist-kit::admin.login') }} · {{ __('laranail/waitlist-kit::admin.name') }}</title>
    {{ $appearanceScript }}
    {{ $adminAssets }}
</head>
<body class="wka d-flex flex-column">
<div class="page page-center">
    <main id="content" class="container container-tight py-4">
        <p class="text-center mb-4 h2 text-secondary">{{ __('laranail/waitlist-kit::admin.name') }}</p>
        @include('laranail/waitlist-kit::admin.partials.errors')
        <div class="card card-md">
            <div class="card-body">
                <h1 class="h2 text-center mb-4">{{ __('laranail/waitlist-kit::admin.sign_in_to') }}</h1>
                <form method="post" action="{{ route('laranail-waitlist-kit.admin.login.store') }}">
                    @csrf
                    @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'email', 'type' => 'email', 'label' => __('laranail/waitlist-kit::admin.email'), 'required' => true, 'autocomplete' => 'username'])
                    @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'password', 'type' => 'password', 'label' => __('laranail/waitlist-kit::admin.password'), 'required' => true, 'value' => '', 'autocomplete' => 'current-password'])
                    @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'remember', 'type' => 'checkbox', 'label' => __('laranail/waitlist-kit::admin.remember')])
                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary w-100">{{ __('laranail/waitlist-kit::admin.login') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
