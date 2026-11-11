{{-- The time left until the campaign's launch date. Without JavaScript, the date itself. --}}
@php($launch = $campaign->launches_at ?? null)
@if ($launch)
    <section class="wk-block wk-countdown" data-block="countdown" x-data="wkCountdown" data-target="{{ $launch->toIso8601String() }}">
        @isset($content['label'])<h2 class="wk-heading">{{ $content['label'] }}</h2>@endisset
        <p class="wk-countdown-value" aria-live="polite" x-text="label">
            <time datetime="{{ $launch->toIso8601String() }}">{{ $launch->toFormattedDateString() }}</time>
        </p>
    </section>
@endif
