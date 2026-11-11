@extends('laranail/waitlist-kit::admin.layout', ['title' => __('laranail/waitlist-kit::admin.nav.enquiries')])

@section('content')
    <table class="wka-table">
        <caption>{{ __('laranail/waitlist-kit::admin.nav.enquiries') }}</caption>
        <thead><tr>
            <th scope="col">{{ __('laranail/waitlist-kit::admin.from') }}</th>
            <th scope="col">{{ __('laranail/waitlist-kit::admin.topic') }}</th>
            <th scope="col">{{ __('laranail/waitlist-kit::admin.campaign') }}</th>
            <th scope="col">{{ __('laranail/waitlist-kit::admin.received') }}</th>
        </tr></thead>
        <tbody>
        @forelse ($enquiries as $enquiry)
            <tr>
                <th scope="row"><a href="{{ route('laranail-waitlist-kit.admin.enquiries.show', $enquiry) }}">{{ $enquiry->name }}</a> &lt;{{ $enquiry->email }}&gt;</th>
                <td>{{ $enquiry->topic }}</td>
                <td>{{ $enquiry->campaign?->name ?? '-' }}</td>
                <td><time datetime="{{ $enquiry->created_at?->toIso8601String() }}">{{ $enquiry->created_at?->toDayDateTimeString() }}</time></td>
            </tr>
        @empty
            <tr><td colspan="4">{{ __('laranail/waitlist-kit::admin.none') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $enquiries->links() }}
@endsection
