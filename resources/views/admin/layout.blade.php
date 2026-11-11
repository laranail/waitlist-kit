{{-- The backend's shell: Tabler's vertical sidebar, a page header (pretitle, title, actions) and the page body.
     A screen sets $title, and optionally $pretitle, @section('actions') and @section('content'). --}}
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="{{ $appearance->initialTheme() }}" data-wk-appearance="{{ $appearance->value }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · {{ __('laranail/waitlist-kit::admin.name') }}</title>
    {{ $appearanceScript }}
    {{ $adminAssets }}
</head>
<body class="wka">
<a class="visually-hidden-focusable skip-link" href="#content">{{ __('laranail/waitlist-kit::admin.skip') }}</a>
<div class="page">
    {{-- The page's banner: brand, menu toggle and the named <nav>. Not <aside>, which made the navigation a nameless
         complementary landmark; the page title below is a plain block, so there is one banner. --}}
    <header class="navbar navbar-vertical navbar-expand-lg" x-data="wkNav">
        <div class="container-fluid">
            <button class="navbar-toggler" type="button" aria-controls="wka-menu" aria-expanded="false"
                    x-bind:aria-expanded="expanded" x-on:click="toggle">
                <span class="navbar-toggler-icon" aria-hidden="true"></span>
                <span class="visually-hidden">{{ __('laranail/waitlist-kit::admin.nav.toggle') }}</span>
            </button>
            <p class="navbar-brand navbar-brand-autodark m-0">
                <a href="{{ route('laranail-waitlist-kit.admin.dashboard') }}" class="text-reset text-decoration-none">{{ __('laranail/waitlist-kit::admin.name') }}</a>
            </p>
            <nav class="collapse navbar-collapse" id="wka-menu" x-bind:class="menuClass" aria-label="{{ __('laranail/waitlist-kit::admin.nav.label') }}">
                <ul class="navbar-nav pt-lg-3">
                    @foreach ($nav as $item)
                        <li class="nav-item @if ($item['current']) active @endif">
                            <a class="nav-link" href="{{ $item['url'] }}" @if ($item['current']) aria-current="page" @endif>
                                <span class="nav-link-icon d-md-none d-lg-inline-block">@include('laranail/waitlist-kit::admin.partials.icon', ['name' => $item['key']])</span>
                                <span class="nav-link-title">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <form method="post" action="{{ route('laranail-waitlist-kit.admin.appearance.update') }}" class="mt-auto px-3 pt-3">
                    @csrf
                    <input type="hidden" name="return" value="{{ $currentPath }}">
                    <fieldset>
                        <legend class="small text-secondary mb-2">{{ __('laranail/waitlist-kit::admin.appearance.label') }}</legend>
                        <div class="btn-group w-100" role="group">
                            @foreach ($appearances as $option)
                                <button type="submit" name="appearance" value="{{ $option->value }}" class="btn btn-sm {{ $option === $appearance ? 'btn-primary' : 'btn-outline-secondary' }}" aria-pressed="{{ $option === $appearance ? 'true' : 'false' }}" title="{{ __('laranail/waitlist-kit::admin.appearance.' . $option->value) }}">
                                    @include('laranail/waitlist-kit::admin.partials.icon', ['name' => match ($option->value) { 'light' => 'sun', 'dark' => 'moon', default => 'system' }])
                                    <span class="visually-hidden">{{ __('laranail/waitlist-kit::admin.appearance.' . $option->value) }}</span>
                                </button>
                            @endforeach
                        </div>
                    </fieldset>
                </form>
                @if ($who)
                    <div class="p-3 border-top mt-3">
                        <p class="mb-2 small text-secondary text-truncate">{{ __('laranail/waitlist-kit::admin.signed_in_as') }} <span class="text-body">{{ $who }}</span></p>
                        @if ($bundled)
                            <form method="post" action="{{ route('laranail-waitlist-kit.admin.logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-secondary w-100">
                                    @include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'logout']) {{ __('laranail/waitlist-kit::admin.logout') }}
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </nav>
        </div>
    </header>

    <div class="page-wrapper">
        {{-- The title sits inside <main>, so the skip link lands on it and every part of the page is in a landmark. --}}
        <main id="content" tabindex="-1">
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            @isset($pretitle)<div class="page-pretitle">{{ $pretitle }}</div>@endisset
                            <h1 class="page-title">{{ $title }}</h1>
                        </div>
                        @hasSection('actions')
                            <div class="col-auto ms-auto d-print-none">
                                <div class="btn-list">@yield('actions')</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="page-body">
            <div class="container-xl">
                @if (session('status'))
                    <div class="alert alert-success" role="status">
                        <div class="d-flex gap-2">
                            @include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'check'])
                            <div>{{ session('status') }}</div>
                        </div>
                    </div>
                @endif
                @include('laranail/waitlist-kit::admin.partials.errors')
                @yield('content')
            </div>
            </div>
        </main>

        <footer class="footer footer-transparent d-print-none">
            <div class="container-xl">
                <p class="m-0 small text-secondary">laranail/waitlist-kit</p>
            </div>
        </footer>
    </div>
</div>
</body>
</html>
