<section class="wk-block wk-contact" data-block="contact">
    @isset($content['title'])<h2 class="wk-heading">{{ $content['title'] }}</h2>@endisset
    <x-laranail-waitlist-kit::contact-form :campaign="$campaign->slug ?? null" x-data="wkForm" x-on:submit="submit" />
</section>
