<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} · {{ __('laranail/waitlist-kit::admin.name') }}</title>
    {{ $adminAssets }}
</head>
<body class="wka">
<a class="wka-skip" href="#content">{{ __('laranail/waitlist-kit::admin.skip') }}</a>
<header class="wka-header">
    <p class="wka-brand">{{ __('laranail/waitlist-kit::admin.name') }}</p>
    <nav aria-label="{{ __('laranail/waitlist-kit::admin.nav.label') }}">
        <ul class="wka-nav">
            @foreach ($nav as $item)
                <li><a href="{{ $item['url'] }}" @if ($item['current']) aria-current="page" @endif>{{ $item['label'] }}</a></li>
            @endforeach
        </ul>
    </nav>
    @if ($who)
        <div class="wka-who">
            <span>{{ $who }}</span>
            @if ($bundled)
                <form method="post" action="{{ route('laranail-waitlist-kit.admin.logout') }}">@csrf<button type="submit" class="wka-link">{{ __('laranail/waitlist-kit::admin.logout') }}</button></form>
            @endif
        </div>
    @endif
</header>
<main id="content" class="wka-main" tabindex="-1">
    <h1>{{ $title }}</h1>
    @if (session('status'))<p class="wka-flash" role="status">{{ session('status') }}</p>@endif
    @include('laranail/waitlist-kit::admin.partials.errors')
    @yield('content')
</main>
</body>
</html>
