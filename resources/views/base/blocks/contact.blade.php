{{-- A region named by its heading (or, without one, by the send action), like the sign-up block. --}}
@php($headingId = 'wk-contact-' . ($block?->id ?? 'form'))
<section class="wk-block wk-contact" data-block="contact" @isset($content['title']) aria-labelledby="{{ $headingId }}" @else aria-label="{{ __('laranail/waitlist-kit::contact.send') }}" @endisset>
    @isset($content['title'])<h2 class="wk-heading" id="{{ $headingId }}">{{ $content['title'] }}</h2>@endisset
    <x-laranail-waitlist-kit::contact-form :campaign="$campaign->slug ?? null" x-data="wkForm" x-on:submit="submit" />
</section>
