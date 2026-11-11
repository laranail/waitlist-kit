{{--
    The page every themed view extends: the theme's prebuilt assets, its settings as --wk-* custom properties (with the
    application's CSP nonce when it sets one), and one <main>. A page passes $meta (CampaignController::meta) for its
    title, description, canonical address, share image, robots and other languages; without it a page is noindex.
--}}
<!doctype html>
<html lang="{{ $locale }}" data-theme="{{ $theme->theme->name }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="{{ $meta['robots'] ?? 'noindex' }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $meta['title'] ?? $title }}</title>
    @isset($meta)
        @if ($meta['description'])<meta name="description" content="{{ $meta['description'] }}">@endif
        <link rel="canonical" href="{{ $meta['canonical'] }}">
        @foreach ($meta['alternates'] as $code => $href)
            <link rel="alternate" hreflang="{{ $code }}" href="{{ $href }}">
        @endforeach
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $meta['title'] }}">
        @if ($meta['description'])<meta property="og:description" content="{{ $meta['description'] }}">@endif
        <meta property="og:url" content="{{ $meta['canonical'] }}">
        <meta property="og:locale" content="{{ str_replace('-', '_', $locale) }}">
        @if ($meta['image'])
            <meta property="og:image" content="{{ $meta['image'] }}">
            <meta name="twitter:card" content="summary_large_image">
        @else
            <meta name="twitter:card" content="summary">
        @endif
    @endisset
    {{ $theme->assets }}
    @if ($theme->css() !== '')
        <style @if ($theme->nonce) nonce="{{ $theme->nonce }}" @endif>{!! $theme->css() !!}</style>
    @endif
</head>
<body class="wk-body">
<main class="wk-main" id="main">
    @yield('content')
</main>
@yield('footer')
</body>
</html>
