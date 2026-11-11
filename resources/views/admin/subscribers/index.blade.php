@extends('laranail/waitlist-kit::admin.layout', ['title' => __('laranail/waitlist-kit::admin.subscribers_of', ['campaign' => $campaign->name]), 'pretitle' => __('laranail/waitlist-kit::admin.nav.campaigns')])

@section('actions')
    <a class="btn btn-outline-secondary" href="{{ route('laranail-waitlist-kit.admin.campaigns.edit', $campaign) }}">{{ $campaign->name }}</a>
    @can(\Simtabi\Laranail\WaitlistKit\Auth\Abilities::EXPORT)
        <a class="btn btn-primary" href="{{ route('laranail-waitlist-kit.admin.subscribers.export', $campaign) }}">@include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'download']) {{ __('laranail/waitlist-kit::admin.export_csv') }}</a>
    @endcan
@endsection

@section('content')
    @can(\Simtabi\Laranail\WaitlistKit\Auth\Abilities::MANAGE)
        <form method="post" action="{{ route('laranail-waitlist-kit.admin.subscribers.invite', $campaign) }}" class="card mb-3" aria-labelledby="invite-title">
            @csrf
            <div class="card-header"><h2 class="card-title" id="invite-title">{{ __('laranail/waitlist-kit::admin.invite_title') }}</h2></div>
            <div class="card-body">
                @if ($campaign->conversion()->inviteUrl === null)
                    <p class="text-secondary mb-0">{{ __('laranail/waitlist-kit::admin.no_invite_url') }} <a href="{{ route('laranail-waitlist-kit.admin.campaigns.edit', $campaign) }}#conversion">{{ __('laranail/waitlist-kit::admin.conversion') }}</a></p>
                @else
                    <p class="text-secondary">{{ __('laranail/waitlist-kit::admin.invite_hint') }}</p>
                    <div class="row g-2 align-items-end">
                        @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'count', 'type' => 'number', 'label' => __('laranail/waitlist-kit::admin.invite_count'), 'value' => 10, 'wrapperClass' => 'col-sm-auto'])
                        <div class="col-sm-auto"><button type="submit" class="btn btn-primary">{{ __('laranail/waitlist-kit::admin.invite_button') }}</button></div>
                    </div>
                @endif
            </div>
        </form>
    @endcan

    <div class="card mb-3">
        <div class="card-header">
            <form method="get" class="row g-2 align-items-end w-100" role="search" aria-label="{{ __('laranail/waitlist-kit::admin.filter') }}">
                @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'q', 'type' => 'search', 'label' => __('laranail/waitlist-kit::admin.search'), 'value' => $search, 'wrapperClass' => 'col-sm'])
                @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'status', 'type' => 'select', 'label' => __('laranail/waitlist-kit::admin.status'), 'value' => $status?->value ?? '', 'wrapperClass' => 'col-sm-auto', 'options' => ['' => __('laranail/waitlist-kit::admin.any')] + collect($statuses)->mapWithKeys(fn ($s) => [$s->value => __('laranail/waitlist-kit::admin.statuses.' . $s->value)])->all()])
                <div class="col-sm-auto"><button type="submit" class="btn btn-outline-primary">@include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'search']) {{ __('laranail/waitlist-kit::admin.filter') }}</button></div>
            </form>
        </div>
        @if ($subscribers->isEmpty())
            <div class="card-body">
                @include('laranail/waitlist-kit::admin.partials.empty', ['title' => __('laranail/waitlist-kit::admin.empty.subscribers'), 'hint' => __('laranail/waitlist-kit::admin.empty.subscribers_hint')])
            </div>
        @else
            <div class="table-responsive" tabindex="0" role="region" aria-labelledby="wka-subscribers-index-table">
                <table class="table table-vcenter card-table">
                    <caption class="px-3" id="wka-subscribers-index-table">{{ trans_choice('laranail/waitlist-kit::admin.subscriber_count', $subscribers->total(), ['count' => number_format($subscribers->total())]) }}</caption>
                    <thead><tr>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.email') }}</th>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.status') }}</th>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.locale') }}</th>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.joined') }}</th>
                        <th scope="col" class="w-1"><span class="visually-hidden">{{ __('laranail/waitlist-kit::admin.actions') }}</span></th>
                    </tr></thead>
                    <tbody>
                    @foreach ($subscribers as $subscriber)
                        <tr>
                            <th scope="row" class="fw-normal">{{ $subscriber->email }}</th>
                            <td>@include('laranail/waitlist-kit::admin.partials.status', ['status' => $subscriber->status])</td>
                            <td><span class="text-uppercase">{{ $subscriber->locale }}</span></td>
                            <td class="text-secondary"><time datetime="{{ $subscriber->created_at?->toIso8601String() }}">{{ $subscriber->created_at?->toDayDateTimeString() }}</time></td>
                            <td>
                                @can(\Simtabi\Laranail\WaitlistKit\Auth\Abilities::MANAGE)
                                    <form method="post" action="{{ route('laranail-waitlist-kit.admin.subscribers.destroy', [$campaign, $subscriber]) }}" x-data="wkConfirm" x-on:submit="check" data-message="{{ __('laranail/waitlist-kit::admin.confirm_remove') }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-ghost-danger">@include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'trash']) {{ __('laranail/waitlist-kit::admin.remove') }}<span class="visually-hidden"> {{ $subscriber->email }}</span></button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if ($subscribers->hasPages())
                <div class="card-footer d-flex align-items-center">{{ $subscribers->links('pagination::bootstrap-5') }}</div>
            @endif
        @endif
    </div>

    @can(\Simtabi\Laranail\WaitlistKit\Auth\Abilities::ERASE)
        <form method="post" action="{{ route('laranail-waitlist-kit.admin.forget') }}" class="card" aria-labelledby="forget">
            @csrf
            <div class="card-status-top bg-danger"></div>
            <div class="card-header"><h2 class="card-title" id="forget">{{ __('laranail/waitlist-kit::admin.forget') }}</h2></div>
            <div class="card-body">
                <p class="text-secondary">{{ __('laranail/waitlist-kit::admin.forget_hint') }}</p>
                <div class="row g-2 align-items-end">
                    @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'email', 'type' => 'email', 'label' => __('laranail/waitlist-kit::admin.email'), 'value' => '', 'wrapperClass' => 'col-sm'])
                    <div class="col-sm-auto"><button type="submit" class="btn btn-danger">{{ __('laranail/waitlist-kit::admin.erase') }}</button></div>
                </div>
            </div>
        </form>
    @endcan
@endsection
