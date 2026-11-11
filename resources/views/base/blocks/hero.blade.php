<header class="wk-block wk-hero" data-block="hero">
    @if (($logo = $theme->theme->setting('logo')?->cssValue($theme->settings['logo'] ?? null)) && $logo !== 'none')
        <span class="wk-logo" role="img" aria-label="{{ $campaign->name ?? '' }}"></span>
    @endif
    <h1 class="wk-hero-title">{{ $content['title'] ?? ($campaign->name ?? '') }}</h1>
    @isset($content['body'])<p class="wk-hero-body">{{ $content['body'] }}</p>@endisset
</header>
