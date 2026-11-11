@extends('laranail/waitlist-kit::admin.layout', ['title' => __('laranail/waitlist-kit::admin.nav.enquiries'), 'pretitle' => __('laranail/waitlist-kit::admin.manage')])

@section('content')
    <div class="card">
        @if ($enquiries->isEmpty())
            <div class="card-body">
                @include('laranail/waitlist-kit::admin.partials.empty', ['title' => __('laranail/waitlist-kit::admin.empty.enquiries'), 'hint' => __('laranail/waitlist-kit::admin.empty.enquiries_hint')])
            </div>
        @else
            <div class="table-responsive" tabindex="0" role="region" aria-labelledby="wka-enquiries-index-table">
                <table class="table table-vcenter card-table">
                    <caption class="visually-hidden" id="wka-enquiries-index-table">{{ __('laranail/waitlist-kit::admin.nav.enquiries') }}</caption>
                    <thead><tr>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.from') }}</th>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.topic') }}</th>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.campaign') }}</th>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.received') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($enquiries as $enquiry)
                        <tr>
                            <th scope="row" class="fw-normal">
                                <a href="{{ route('laranail-waitlist-kit.admin.enquiries.show', $enquiry) }}">{{ $enquiry->name }}</a>
                                <div class="text-secondary small">{{ $enquiry->email }}</div>
                            </th>
                            <td><span class="badge bg-azure-lt">{{ $enquiry->topic }}</span></td>
                            <td>{{ $enquiry->campaign?->name ?? '-' }}</td>
                            <td class="text-secondary"><time datetime="{{ $enquiry->created_at?->toIso8601String() }}">{{ $enquiry->created_at?->toDayDateTimeString() }}</time></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($enquiries->hasPages())
                <div class="card-footer d-flex align-items-center">{{ $enquiries->links('pagination::bootstrap-5') }}</div>
            @endif
        @endif
    </div>
@endsection
