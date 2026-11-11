{{--
    The kit's plain page for a live campaign: semantic HTML that works without JavaScript or CSS. Themes (Phase 2)
    replace it. Blocks render in order; the form block is the sign-up form, and a campaign without one still gets it.
--}}
@php
    $text = fn ($block) => $block->contentFor($locale, $campaign->locales[0] ?? 'en');
    $hasForm = $blocks->contains('type', 'form');
@endphp
<!doctype html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $campaign->name }}</title>
</head>
<body>
<main>
    @foreach ($blocks as $block)
        @php($content = $text($block))
        @switch($block->type)
            @case('hero')
                <header>
                    <h1>{{ $content['title'] ?? $campaign->name }}</h1>
                    @isset($content['body'])<p>{{ $content['body'] }}</p>@endisset
                </header>
                @break
            @case('form')
                @include('laranail/waitlist-kit::partials.signup-form', ['label' => $content['label'] ?? null, 'button' => $content['button'] ?? null])
                @break
            @case('faq')
                <section aria-labelledby="faq-{{ $block->id }}">
                    <h2 id="faq-{{ $block->id }}">{{ __('laranail/waitlist-kit::page.faq') }}</h2>
                    <dl>
                        @foreach ($content['items'] ?? [] as $item)
                            <dt>{{ $item['question'] ?? '' }}</dt>
                            <dd>{{ $item['answer'] ?? '' }}</dd>
                        @endforeach
                    </dl>
                </section>
                @break
        @endswitch
    @endforeach

    @unless ($hasForm)
        @if ($blocks->isEmpty())<h1>{{ $campaign->name }}</h1>@endif
        @include('laranail/waitlist-kit::partials.signup-form', ['label' => null, 'button' => null])
    @endunless
</main>
</body>
</html>
