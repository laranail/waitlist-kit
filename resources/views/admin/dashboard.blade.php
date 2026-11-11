@extends('laranail/waitlist-kit::admin.layout', ['title' => __('laranail/waitlist-kit::admin.nav.dashboard')])

@section('content')
    @if ($problems !== [])
        <section class="wka-panel wka-warning" aria-labelledby="problems">
            <h2 id="problems">{{ __('laranail/waitlist-kit::admin.problems') }}</h2>
            <ul>@foreach ($problems as $problem)<li>{{ $problem }}</li>@endforeach</ul>
        </section>
    @endif

    <table class="wka-table">
        <caption>{{ __('laranail/waitlist-kit::admin.campaigns_summary') }}</caption>
        <thead><tr>
            <th scope="col">{{ __('laranail/waitlist-kit::admin.campaign') }}</th>
            <th scope="col">{{ __('laranail/waitlist-kit::admin.status') }}</th>
            <th scope="col" class="wka-num">{{ __('laranail/waitlist-kit::admin.subscribers') }}</th>
            <th scope="col" class="wka-num">{{ __('laranail/waitlist-kit::admin.this_week') }}</th>
        </tr></thead>
        <tbody>
        @forelse ($campaigns as $campaign)
            <tr>
                <th scope="row"><a href="{{ route('laranail-waitlist-kit.admin.campaigns.edit', $campaign) }}">{{ $campaign->name }}</a></th>
                <td>{{ $campaign->status->value }}</td>
                <td class="wka-num"><a href="{{ route('laranail-waitlist-kit.admin.subscribers.index', $campaign) }}">{{ number_format($campaign->subscribers_count) }}</a></td>
                <td class="wka-num">{{ number_format((int) ($week[$campaign->id] ?? 0)) }}</td>
            </tr>
        @empty
            <tr><td colspan="4">{{ __('laranail/waitlist-kit::admin.none') }} <a href="{{ route('laranail-waitlist-kit.admin.campaigns.create') }}">{{ __('laranail/waitlist-kit::admin.new_campaign') }}</a></td></tr>
        @endforelse
        </tbody>
    </table>

    <p><a href="{{ route('laranail-waitlist-kit.admin.enquiries.index') }}">{{ trans_choice('laranail/waitlist-kit::admin.enquiries_week', $enquiries, ['count' => $enquiries]) }}</a></p>
@endsection
