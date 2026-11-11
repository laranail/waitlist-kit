@extends($theme->view('layouts.page'), ['title' => $page->titleFor($locale)])

@section('content')
    <article class="wk-page" data-kind="{{ $page->kind->value }}">
        <h1 class="wk-page-title">{{ $page->titleFor($locale) }}</h1>
        @foreach ($blocks as $block)
            @if ($view = $theme->block($block->type))
                @include($view, ['content' => $block->contentFor($locale), 'block' => $block])
            @endif
        @endforeach
        <p class="wk-page-updated"><small>{{ __('laranail/waitlist-kit::legal.updated', ['date' => $page->updated_at?->toDateString()]) }}</small></p>
    </article>
    @include($theme->view('blocks.footer'), ['content' => [], 'block' => null])
@endsection
