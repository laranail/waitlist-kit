@if ($pages->isNotEmpty())
    <ul {{ $attributes->merge(['data-scope' => 'legal-links']) }}>
        @foreach ($pages as $page)
            <li data-part="item"><a data-part="link" href="{{ route('laranail-waitlist-kit.page.show', $page) }}">{{ $page->titleFor(app()->getLocale()) }}</a></li>
        @endforeach
    </ul>
@endif
