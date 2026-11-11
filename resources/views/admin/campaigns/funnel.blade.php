@extends('laranail/waitlist-kit::admin.layout', ['title' => __('laranail/waitlist-kit::admin.funnel_of', ['campaign' => $campaign->name]), 'pretitle' => __('laranail/waitlist-kit::admin.nav.campaigns')])

@php($types = \Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType::class)

@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('laranail-waitlist-kit.admin.campaigns.edit', $campaign) }}">{{ $campaign->name }}</a>
    <a class="btn btn-outline-secondary" href="{{ route('laranail-waitlist-kit.admin.subscribers.index', $campaign) }}">@include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'subscribers']) {{ __('laranail/waitlist-kit::admin.subscribers') }}</a>
@endsection

@section('content')
    <p class="text-secondary">{{ trans_choice('laranail/waitlist-kit::admin.funnel_window', $funnel->days, ['days' => $funnel->days]) }}</p>

    <h2 class="visually-hidden">{{ __('laranail/waitlist-kit::admin.funnel_steps') }}</h2>
    <div class="row row-deck row-cards mb-3">
        @foreach (['view', 'signup', 'confirm', 'referral', 'invite', 'convert', 'unsubscribe'] as $step)
            <div class="col-6 col-md-4 col-xl">
                <div class="card">
                    <div class="card-body">
                        <p class="subheader mb-2">{{ __('laranail/waitlist-kit::admin.steps.' . $step) }}</p>
                        <p class="h1 mb-0">{{ number_format($funnel->count($types::from($step))) }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row row-cards">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('laranail/waitlist-kit::admin.rates') }}</h2></div>
                <div class="card-body">
                    <dl class="datagrid">
                        @foreach ([['view', 'signup'], ['signup', 'confirm'], ['invite', 'convert']] as [$from, $to])
                            @php($rate = $funnel->rate($types::from($from), $types::from($to)))
                            <div class="datagrid-item">
                                <dt class="datagrid-title">{{ __('laranail/waitlist-kit::admin.rate_' . $from . '_' . $to) }}</dt>
                                <dd class="datagrid-content">{{ $rate === null ? '-' : number_format($rate, 1) . '%' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>

            <div class="card mt-3">
                <div class="table-responsive" tabindex="0" role="region" aria-labelledby="wka-funnel-sources">
                    <table class="table table-vcenter card-table">
                        <caption class="px-3" id="wka-funnel-sources">{{ __('laranail/waitlist-kit::admin.top_sources') }}</caption>
                        <thead><tr>
                            <th scope="col">{{ __('laranail/waitlist-kit::admin.source') }}</th>
                            <th scope="col" class="text-end">{{ __('laranail/waitlist-kit::admin.steps.signup') }}</th>
                        </tr></thead>
                        <tbody>
                        @forelse ($funnel->sources as $source => $total)
                            <tr><th scope="row" class="fw-normal">{{ $source }}</th><td class="text-end">{{ number_format($total) }}</td></tr>
                        @empty
                            <tr><td colspan="2" class="text-secondary">{{ __('laranail/waitlist-kit::admin.none') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="table-responsive" tabindex="0" role="region" aria-labelledby="wka-funnel-days">
                    <table class="table table-vcenter card-table">
                        <caption class="px-3" id="wka-funnel-days">{{ __('laranail/waitlist-kit::admin.signups_by_day') }}</caption>
                        <thead><tr>
                            <th scope="col">{{ __('laranail/waitlist-kit::admin.day') }}</th>
                            <th scope="col" class="text-end">{{ __('laranail/waitlist-kit::admin.steps.signup') }}</th>
                        </tr></thead>
                        <tbody>
                        @foreach (array_reverse($funnel->byDay, true) as $day => $total)
                            <tr><th scope="row" class="fw-normal"><time datetime="{{ $day }}">{{ \Illuminate\Support\Carbon::parse($day)->toFormattedDateString() }}</time></th><td class="text-end">{{ number_format($total) }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
