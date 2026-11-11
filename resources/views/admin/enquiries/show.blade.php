@extends('laranail/waitlist-kit::admin.layout', ['title' => __('laranail/waitlist-kit::admin.enquiry_from', ['name' => $enquiry->name])])

@section('content')
    <dl class="wka-facts">
        <dt>{{ __('laranail/waitlist-kit::admin.email') }}</dt><dd><a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></dd>
        <dt>{{ __('laranail/waitlist-kit::admin.company') }}</dt><dd>{{ $enquiry->company ?? '-' }}</dd>
        <dt>{{ __('laranail/waitlist-kit::admin.topic') }}</dt><dd>{{ $enquiry->topic }}</dd>
        <dt>{{ __('laranail/waitlist-kit::admin.locale') }}</dt><dd>{{ $enquiry->locale }}</dd>
        <dt>{{ __('laranail/waitlist-kit::admin.received') }}</dt><dd>{{ $enquiry->created_at?->toDayDateTimeString() }}</dd>
        <dt>{{ __('laranail/waitlist-kit::admin.consent') }}</dt><dd>{{ $enquiry->consent['agreed_at'] ?? __('laranail/waitlist-kit::admin.not_asked') }}</dd>
    </dl>
    <h2>{{ __('laranail/waitlist-kit::admin.message') }}</h2>
    <p class="wka-message">{{ $enquiry->message }}</p>
    <p><a href="{{ route('laranail-waitlist-kit.admin.enquiries.index') }}">{{ __('laranail/waitlist-kit::admin.back') }}</a></p>
@endsection
