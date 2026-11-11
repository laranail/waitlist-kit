@extends($theme->view('layouts.page'), ['title' => $campaign->name])

@section('content')
    @php($fallback = $campaign->locales[0] ?? 'en')
    @php($hasForm = $blocks->contains('type', 'form'))

    @foreach ($blocks->reject(fn ($block) => $block->type === 'footer') as $block)
        @if ($view = $theme->block($block->type))
            @include($view, ['content' => $block->contentFor($locale, $fallback), 'block' => $block])
        @endif
    @endforeach

    @unless ($hasForm)
        @include($theme->view('blocks.form'), ['content' => [], 'block' => null])
    @endunless
@endsection

{{-- Footer blocks render after <main>, so the page's <footer> is its contentinfo landmark rather than part of main. --}}
@section('footer')
    @foreach ($blocks->filter(fn ($block) => $block->type === 'footer') as $block)
        @if ($view = $theme->block($block->type))
            @include($view, ['content' => $block->contentFor($locale, $campaign->locales[0] ?? 'en'), 'block' => $block])
        @endif
    @endforeach
@endsection
