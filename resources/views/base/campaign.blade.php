@extends($theme->view('layouts.page'), ['title' => $campaign->name])

@section('content')
    @php($fallback = $campaign->locales[0] ?? 'en')
    @php($hasForm = $blocks->contains('type', 'form'))
    @php($comingSoon = $campaign->type === \Simtabi\Laranail\WaitlistKit\Enums\CampaignType::ComingSoon)

    {{-- Coming soon: the countdown leads, even without a countdown block, once there is a launch date. --}}
    @if ($comingSoon && $campaign->launches_at && ! $blocks->contains('type', 'countdown'))
        @include($theme->view('blocks.countdown'), ['content' => [], 'block' => null])
    @endif

    @foreach ($blocks->reject(fn ($block) => $block->type === 'footer') as $block)
        @if ($view = $theme->block($block->type))
            @include($view, ['content' => $block->contentFor($locale, $fallback), 'block' => $block])
        @endif
    @endforeach

    @unless ($hasForm)
        @include($theme->view('blocks.form'), ['content' => [], 'block' => null])
    @endunless

    @if ($access ?? false)
        @include('laranail/waitlist-kit::partials.access-form')
    @endif
@endsection

{{-- Footer blocks render after <main>, so the page's <footer> is its contentinfo landmark rather than part of main. --}}
@section('footer')
    @foreach ($blocks->filter(fn ($block) => $block->type === 'footer') as $block)
        @if ($view = $theme->block($block->type))
            @include($view, ['content' => $block->contentFor($locale, $campaign->locales[0] ?? 'en'), 'block' => $block])
        @endif
    @endforeach
@endsection
