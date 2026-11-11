@extends('laranail/waitlist-kit::admin.layout', ['title' => __('laranail/waitlist-kit::admin.nav.campaigns'), 'pretitle' => __('laranail/waitlist-kit::admin.manage')])

@section('actions')
    @can(\Simtabi\Laranail\WaitlistKit\Auth\Abilities::MANAGE)
        @if ($limit === null || $campaigns->count() < $limit)
            <a class="btn btn-primary" href="{{ route('laranail-waitlist-kit.admin.campaigns.create') }}">@include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'plus']) {{ __('laranail/waitlist-kit::admin.new_campaign') }}</a>
        @endif
    @endcan
@endsection

@section('content')
    @can(\Simtabi\Laranail\WaitlistKit\Auth\Abilities::MANAGE)
        @if ($limit !== null && $campaigns->count() >= $limit)
            <div class="alert alert-info" role="status">{{ trans_choice('laranail/waitlist-kit::admin.limit_reached', $limit, ['limit' => $limit]) }}</div>
        @endif
    @endcan

    <div class="card">
        @if ($campaigns->isEmpty())
            <div class="card-body">
                @include('laranail/waitlist-kit::admin.partials.empty', ['title' => __('laranail/waitlist-kit::admin.empty.campaigns'), 'hint' => __('laranail/waitlist-kit::admin.empty.campaigns_hint')])
            </div>
        @else
            <div class="table-responsive" tabindex="0" role="region" aria-labelledby="wka-campaigns-index-table">
                <table class="table table-vcenter card-table">
                    <caption class="visually-hidden" id="wka-campaigns-index-table">{{ __('laranail/waitlist-kit::admin.nav.campaigns') }}</caption>
                    <thead><tr>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.campaign') }}</th>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.type') }}</th>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.status') }}</th>
                        <th scope="col" class="text-end">{{ __('laranail/waitlist-kit::admin.subscribers') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($campaigns as $campaign)
                        <tr>
                            <th scope="row" class="fw-normal">
                                <a href="{{ route('laranail-waitlist-kit.admin.campaigns.edit', $campaign) }}">{{ $campaign->name }}</a>
                                <div class="text-secondary small"><code>{{ $campaign->slug }}</code></div>
                            </th>
                            <td>{{ str_replace('_', ' ', ucfirst($campaign->type->value)) }}</td>
                            <td>@include('laranail/waitlist-kit::admin.partials.status', ['status' => $campaign->status])</td>
                            <td class="text-end"><a href="{{ route('laranail-waitlist-kit.admin.subscribers.index', $campaign) }}">{{ number_format($campaign->subscribers_count) }}</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
