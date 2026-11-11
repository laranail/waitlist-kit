@extends($theme->view('layouts.page'), ['title' => $campaign->name])

@section('content')
    @php($fallback = $campaign->locales[0] ?? 'en')
    @php($hasForm = $blocks->contains('type', 'form'))

    @foreach ($blocks as $block)
        @if ($view = $theme->block($block->type))
            @include($view, ['content' => $block->contentFor($locale, $fallback), 'block' => $block])
        @endif
    @endforeach

    @unless ($hasForm)
        @include($theme->view('blocks.form'), ['content' => [], 'block' => null])
    @endunless
@endsection
