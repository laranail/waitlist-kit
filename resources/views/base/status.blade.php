{{-- A subscriber's status page: their place, their referrals and their referral link. No address is shown. --}}
@extends($theme->view('layouts.page'), ['title' => __('laranail/waitlist-kit::page.status_title') . ' · ' . $campaign->name])

@section('content')
    <section class="wk-block wk-status-page" aria-labelledby="wk-status-title">
        <h1 class="wk-page-title" id="wk-status-title">{{ __('laranail/waitlist-kit::page.status_title') }}</h1>
        <p class="wk-status-position">{{ __('laranail/waitlist-kit::page.status_position', ['position' => number_format((int) $subscriber->position)]) }}</p>
        <p>{{ trans_choice('laranail/waitlist-kit::page.status_referrals', $referrals, ['count' => number_format($referrals)]) }}</p>
    </section>

    @if ($conversion->referrals)
        <section class="wk-block wk-share" aria-labelledby="wk-share-title">
            <h2 class="wk-heading" id="wk-share-title">{{ __('laranail/waitlist-kit::page.share_title') }}</h2>
            @if ($conversion->referralBump > 0)
                <p>{{ trans_choice('laranail/waitlist-kit::page.share_hint', $conversion->referralBump, ['bump' => $conversion->referralBump]) }}</p>
            @endif
            <div class="wk-field">
                <label class="wk-label" for="wk-referral-link">{{ __('laranail/waitlist-kit::page.referral_link') }}</label>
                <input class="wk-input" id="wk-referral-link" type="url" value="{{ $link }}" readonly>
            </div>
            <ul class="wk-share-links">
                <li><a href="{{ $shares['email'] }}">{{ __('laranail/waitlist-kit::page.share_email') }}</a></li>
                <li><a href="{{ $shares['x'] }}" rel="noopener noreferrer" target="_blank">{{ __('laranail/waitlist-kit::page.share_x') }}<span class="wk-sr"> {{ __('laranail/waitlist-kit::page.new_window') }}</span></a></li>
                <li><a href="{{ $shares['linkedin'] }}" rel="noopener noreferrer" target="_blank">{{ __('laranail/waitlist-kit::page.share_linkedin') }}<span class="wk-sr"> {{ __('laranail/waitlist-kit::page.new_window') }}</span></a></li>
            </ul>
        </section>
    @endif
@endsection
