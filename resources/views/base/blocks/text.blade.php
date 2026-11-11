<section class="wk-block wk-text" data-block="text">
    @isset($content['heading'])<h2 class="wk-heading">{{ $content['heading'] }}</h2>@endisset
    @foreach ((array) ($content['paragraphs'] ?? []) as $paragraph)
        <p>{{ $paragraph }}</p>
    @endforeach
</section>
