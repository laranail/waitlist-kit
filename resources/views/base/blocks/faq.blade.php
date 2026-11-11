<section class="wk-block wk-faq" data-block="faq">
    <h2 class="wk-heading">{{ $content['title'] ?? __('laranail/waitlist-kit::page.faq') }}</h2>
    @foreach ((array) ($content['items'] ?? []) as $item)
        <details class="wk-faq-item">
            <summary class="wk-faq-question">{{ $item['question'] ?? '' }}</summary>
            <p class="wk-faq-answer">{{ $item['answer'] ?? '' }}</p>
        </details>
    @endforeach
</section>
