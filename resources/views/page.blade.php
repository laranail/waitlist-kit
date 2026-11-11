{{-- A kit-managed page: its title and its blocks (headings and paragraphs). Themes (Phase 2) replace it. --}}
<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->titleFor($locale) }}</title>
</head>
<body>
<main data-scope="page" data-kind="{{ $page->kind->value }}">
    <h1>{{ $page->titleFor($locale) }}</h1>
    @foreach ($blocks as $block)
        @php($content = $block->contentFor($locale))
        @if (isset($content['heading']))<h2>{{ $content['heading'] }}</h2>@endif
        @foreach ((array) ($content['paragraphs'] ?? []) as $paragraph)
            <p>{{ $paragraph }}</p>
        @endforeach
    @endforeach
    <p><small>{{ __('laranail/waitlist-kit::legal.updated', ['date' => $page->updated_at?->toDateString()]) }}</small></p>
</main>
</body>
</html>
