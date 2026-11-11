<section class="wk-block wk-social-proof" data-block="social_proof">
    @isset($content['quote'])
        <figure class="wk-quote">
            <blockquote class="wk-quote-text"><p>{{ $content['quote'] }}</p></blockquote>
            @isset($content['author'])<figcaption class="wk-quote-author">{{ $content['author'] }}</figcaption>@endisset
        </figure>
    @endisset
</section>
