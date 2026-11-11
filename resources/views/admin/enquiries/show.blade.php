@extends('laranail/waitlist-kit::admin.layout', ['title' => __('laranail/waitlist-kit::admin.enquiry_from', ['name' => $enquiry->name]), 'pretitle' => __('laranail/waitlist-kit::admin.nav.enquiries')])

@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('laranail-waitlist-kit.admin.enquiries.index') }}">{{ __('laranail/waitlist-kit::admin.back') }}</a>
    <a class="btn btn-primary" href="mailto:{{ $enquiry->email }}">@include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'mail']) {{ __('laranail/waitlist-kit::admin.reply') }}</a>
@endsection

@section('content')
    <div class="row row-cards">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h2 class="card-title">{{ __('laranail/waitlist-kit::admin.message') }}</h2></div>
                <div class="card-body"><p class="wka-message mb-0">{{ $enquiry->message }}</p></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <dl class="datagrid">
                        <div class="datagrid-item"><dt class="datagrid-title">{{ __('laranail/waitlist-kit::admin.email') }}</dt><dd class="datagrid-content"><a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></dd></div>
                        <div class="datagrid-item"><dt class="datagrid-title">{{ __('laranail/waitlist-kit::admin.company') }}</dt><dd class="datagrid-content">{{ $enquiry->company ?? '-' }}</dd></div>
                        <div class="datagrid-item"><dt class="datagrid-title">{{ __('laranail/waitlist-kit::admin.topic') }}</dt><dd class="datagrid-content">{{ $enquiry->topic }}</dd></div>
                        <div class="datagrid-item"><dt class="datagrid-title">{{ __('laranail/waitlist-kit::admin.locale') }}</dt><dd class="datagrid-content text-uppercase">{{ $enquiry->locale }}</dd></div>
                        <div class="datagrid-item"><dt class="datagrid-title">{{ __('laranail/waitlist-kit::admin.received') }}</dt><dd class="datagrid-content">{{ $enquiry->created_at?->toDayDateTimeString() }}</dd></div>
                        <div class="datagrid-item"><dt class="datagrid-title">{{ __('laranail/waitlist-kit::admin.consent') }}</dt><dd class="datagrid-content">{{ $enquiry->consent['agreed_at'] ?? __('laranail/waitlist-kit::admin.not_asked') }}</dd></div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
