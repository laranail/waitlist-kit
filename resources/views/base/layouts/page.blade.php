{{--
    The page every themed view extends: the theme's prebuilt assets, its settings as --wk-* custom properties (with the
    application's CSP nonce when it sets one), and one <main>. Not indexed until launch: SEO arrives in Phase 5.
--}}
<!doctype html>
<html lang="{{ $locale }}" data-theme="{{ $theme->theme->name }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }}</title>
    {{ $theme->assets }}
    @if ($theme->css() !== '')
        <style @if ($theme->nonce) nonce="{{ $theme->nonce }}" @endif>{!! $theme->css() !!}</style>
    @endif
</head>
<body class="wk-body">
<main class="wk-main" id="main">
    @yield('content')
</main>
</body>
</html>
