{{-- Leaving the list: one button before, a plain sentence after. No address is shown. --}}
@extends($theme->view('layouts.page'), ['title' => __('laranail/waitlist-kit::page.unsubscribe_title') . ' · ' . $campaign->name])

@section('content')
    <section class="wk-block" aria-labelledby="wk-unsubscribe-title">
        <h1 class="wk-page-title" id="wk-unsubscribe-title">{{ __('laranail/waitlist-kit::page.unsubscribe_title') }}</h1>
        @if ($done)
            <p class="wk-status" role="status" tabindex="-1" autofocus>{{ __('laranail/waitlist-kit::page.unsubscribed', ['campaign' => $campaign->name]) }}</p>
        @else
            <p>{{ __('laranail/waitlist-kit::page.unsubscribe_body', ['campaign' => $campaign->name]) }}</p>
            <form class="wk-form" method="post" action="{{ $action }}">
                @csrf
                <button class="wk-button" type="submit">{{ __('laranail/waitlist-kit::page.unsubscribe_button') }}</button>
            </form>
        @endif
    </section>
@endsection
