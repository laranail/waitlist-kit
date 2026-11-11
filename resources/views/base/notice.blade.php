{{-- A campaign that is closed, or launched without a launch address: what happened, and no form. --}}
@extends($theme->view('layouts.page'), ['title' => $campaign->name])

@section('content')
    <section class="wk-block wk-hero wk-notice" data-state="{{ $kind }}" aria-labelledby="wk-notice-title">
        <h1 class="wk-hero-title" id="wk-notice-title">{{ __('laranail/waitlist-kit::page.' . $kind . '_title', ['campaign' => $campaign->name]) }}</h1>
        <p class="wk-hero-body">{{ __('laranail/waitlist-kit::page.' . $kind . '_body', ['campaign' => $campaign->name]) }}</p>
    </section>
@endsection
