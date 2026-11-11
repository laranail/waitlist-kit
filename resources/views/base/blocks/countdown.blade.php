{{-- The time left until the campaign's launch date. Without JavaScript, the date itself. --}}
@php($launch = $campaign->launches_at ?? null)
@if ($launch)
    <section class="wk-block wk-countdown" data-block="countdown" x-data="wkCountdown" data-target="{{ $launch->toIso8601String() }}">
        @isset($content['label'])<h2 class="wk-heading">{{ $content['label'] }}</h2>@endisset
        {{-- No live region: a minute-by-minute announcement interrupts a screen reader for as long as the page is open.
             The ticking label is visual only; assistive technology reads the launch date, hidden once the label shows. --}}
        <p class="wk-countdown-value">
            <span aria-hidden="true" x-show="label" x-text="label"></span>
            <time x-bind:class="dateClass" datetime="{{ $launch->toIso8601String() }}">{{ $launch->toFormattedDateString() }}</time>
        </p>
    </section>
@endif
