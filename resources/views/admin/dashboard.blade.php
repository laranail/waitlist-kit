@extends('laranail/waitlist-kit::admin.layout', ['title' => __('laranail/waitlist-kit::admin.nav.dashboard'), 'pretitle' => __('laranail/waitlist-kit::admin.overview')])

@section('actions')
    @can(\Simtabi\Laranail\WaitlistKit\Auth\Abilities::MANAGE)
        <a class="btn btn-primary" href="{{ route('laranail-waitlist-kit.admin.campaigns.create') }}">@include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'plus']) {{ __('laranail/waitlist-kit::admin.new_campaign') }}</a>
    @endcan
@endsection

@section('content')
    @if ($problems !== [])
        <div class="alert alert-warning" role="region" aria-labelledby="problems">
            <div class="d-flex gap-2">
                @include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'alert'])
                <div>
                    <h2 class="alert-heading h4" id="problems">{{ __('laranail/waitlist-kit::admin.problems') }}</h2>
                    <ul class="mb-0 ps-3">@foreach ($problems as $problem)<li>{{ $problem }}</li>@endforeach</ul>
                </div>
            </div>
        </div>
    @endif

    @php
        $stats = [
            ['campaigns', $campaigns->count(), null],
            ['live', $campaigns->filter(fn ($c) => $c->acceptsSignups())->count(), null],
            ['subscribers', $campaigns->sum('subscribers_count'), null],
            ['week', (int) $week->sum(), null],
            ['enquiries', $enquiries, route('laranail-waitlist-kit.admin.enquiries.index')],
        ];
    @endphp
    <h2 class="visually-hidden">{{ __('laranail/waitlist-kit::admin.overview') }}</h2>
    <div class="row row-deck row-cards mb-3">
        @foreach ($stats as [$key, $number, $link])
            <div class="col-sm-6 col-lg">
                <div class="card">
                    <div class="card-body">
                        <p class="subheader mb-2">{{ __('laranail/waitlist-kit::admin.stats.' . $key) }}</p>
                        <p class="h1 mb-0">
                            @if ($link)<a href="{{ $link }}" class="text-reset">{{ number_format($number) }}</a>@else{{ number_format($number) }}@endif
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">{{ __('laranail/waitlist-kit::admin.nav.campaigns') }}</h2>
        </div>
        @if ($campaigns->isEmpty())
            <div class="card-body">
                @include('laranail/waitlist-kit::admin.partials.empty', ['title' => __('laranail/waitlist-kit::admin.empty.campaigns'), 'hint' => __('laranail/waitlist-kit::admin.empty.campaigns_hint'), 'actionUrl' => route('laranail-waitlist-kit.admin.campaigns.create'), 'actionLabel' => __('laranail/waitlist-kit::admin.new_campaign')])
            </div>
        @else
            <div class="table-responsive" tabindex="0" role="region" aria-labelledby="wka-dashboard-table">
                <table class="table table-vcenter card-table">
                    <caption class="visually-hidden" id="wka-dashboard-table">{{ __('laranail/waitlist-kit::admin.campaigns_summary') }}</caption>
                    <thead><tr>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.campaign') }}</th>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.status') }}</th>
                        <th scope="col" class="text-end">{{ __('laranail/waitlist-kit::admin.subscribers') }}</th>
                        <th scope="col" class="text-end">{{ __('laranail/waitlist-kit::admin.this_week') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($campaigns as $campaign)
                        <tr>
                            <th scope="row" class="fw-normal"><a href="{{ route('laranail-waitlist-kit.admin.campaigns.edit', $campaign) }}">{{ $campaign->name }}</a></th>
                            <td>@include('laranail/waitlist-kit::admin.partials.status', ['status' => $campaign->status])</td>
                            <td class="text-end"><a href="{{ route('laranail-waitlist-kit.admin.subscribers.index', $campaign) }}">{{ number_format($campaign->subscribers_count) }}</a></td>
                            <td class="text-end">{{ number_format((int) ($week[$campaign->id] ?? 0)) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
