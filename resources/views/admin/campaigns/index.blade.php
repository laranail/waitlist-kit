@extends('laranail/waitlist-kit::admin.layout', ['title' => __('laranail/waitlist-kit::admin.nav.campaigns')])

@section('content')
    @can(\Simtabi\Laranail\WaitlistKit\Auth\Abilities::MANAGE)
        @if ($limit === null || $campaigns->count() < $limit)
            <p><a class="wka-button" href="{{ route('laranail-waitlist-kit.admin.campaigns.create') }}">{{ __('laranail/waitlist-kit::admin.new_campaign') }}</a></p>
        @else
            <p class="wka-hint">{{ trans_choice('laranail/waitlist-kit::admin.limit_reached', $limit, ['limit' => $limit]) }}</p>
        @endif
    @endcan

    <table class="wka-table">
        <caption>{{ __('laranail/waitlist-kit::admin.nav.campaigns') }}</caption>
        <thead><tr>
            <th scope="col">{{ __('laranail/waitlist-kit::admin.campaign') }}</th>
            <th scope="col">{{ __('laranail/waitlist-kit::admin.type') }}</th>
            <th scope="col">{{ __('laranail/waitlist-kit::admin.status') }}</th>
            <th scope="col" class="wka-num">{{ __('laranail/waitlist-kit::admin.subscribers') }}</th>
        </tr></thead>
        <tbody>
        @forelse ($campaigns as $campaign)
            <tr>
                <th scope="row"><a href="{{ route('laranail-waitlist-kit.admin.campaigns.edit', $campaign) }}">{{ $campaign->name }}</a> <code>{{ $campaign->slug }}</code></th>
                <td>{{ $campaign->type->value }}</td>
                <td>{{ $campaign->status->value }}</td>
                <td class="wka-num"><a href="{{ route('laranail-waitlist-kit.admin.subscribers.index', $campaign) }}">{{ number_format($campaign->subscribers_count) }}</a></td>
            </tr>
        @empty
            <tr><td colspan="4">{{ __('laranail/waitlist-kit::admin.none') }}</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection
