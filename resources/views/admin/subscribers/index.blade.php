@extends('laranail/waitlist-kit::admin.layout', ['title' => __('laranail/waitlist-kit::admin.subscribers_of', ['campaign' => $campaign->name])])

@section('content')
    <form method="get" class="wka-form wka-inline" role="search" aria-label="{{ __('laranail/waitlist-kit::admin.filter') }}">
        @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'q', 'type' => 'search', 'label' => __('laranail/waitlist-kit::admin.search'), 'value' => $search])
        @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'status', 'type' => 'select', 'label' => __('laranail/waitlist-kit::admin.status'), 'value' => $status?->value ?? '', 'options' => ['' => __('laranail/waitlist-kit::admin.any')] + collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->value])->all()])
        <button type="submit" class="wka-button">{{ __('laranail/waitlist-kit::admin.filter') }}</button>
    </form>

    @can(\Simtabi\Laranail\WaitlistKit\Auth\Abilities::EXPORT)
        <p><a href="{{ route('laranail-waitlist-kit.admin.subscribers.export', $campaign) }}">{{ __('laranail/waitlist-kit::admin.export_csv') }}</a></p>
    @endcan

    <table class="wka-table">
        <caption>{{ trans_choice('laranail/waitlist-kit::admin.subscriber_count', $subscribers->total(), ['count' => number_format($subscribers->total())]) }}</caption>
        <thead><tr>
            <th scope="col">{{ __('laranail/waitlist-kit::admin.email') }}</th>
            <th scope="col">{{ __('laranail/waitlist-kit::admin.status') }}</th>
            <th scope="col">{{ __('laranail/waitlist-kit::admin.locale') }}</th>
            <th scope="col">{{ __('laranail/waitlist-kit::admin.joined') }}</th>
            <th scope="col"><span class="wka-sr">{{ __('laranail/waitlist-kit::admin.actions') }}</span></th>
        </tr></thead>
        <tbody>
        @forelse ($subscribers as $subscriber)
            <tr>
                <th scope="row">{{ $subscriber->email }}</th>
                <td>{{ $subscriber->status->value }}</td>
                <td>{{ $subscriber->locale }}</td>
                <td><time datetime="{{ $subscriber->created_at?->toIso8601String() }}">{{ $subscriber->created_at?->toDayDateTimeString() }}</time></td>
                <td>
                    @can(\Simtabi\Laranail\WaitlistKit\Auth\Abilities::MANAGE)
                        <form method="post" action="{{ route('laranail-waitlist-kit.admin.subscribers.destroy', [$campaign, $subscriber]) }}" x-data="wkConfirm" x-on:submit="check" data-message="{{ __('laranail/waitlist-kit::admin.confirm_remove') }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="wka-link wka-danger-text">{{ __('laranail/waitlist-kit::admin.remove') }}<span class="wka-sr"> {{ $subscriber->email }}</span></button>
                        </form>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="5">{{ __('laranail/waitlist-kit::admin.none') }}</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $subscribers->links() }}

    @can(\Simtabi\Laranail\WaitlistKit\Auth\Abilities::ERASE)
        <section class="wka-panel wka-danger" aria-labelledby="forget">
            <h2 id="forget">{{ __('laranail/waitlist-kit::admin.forget') }}</h2>
            <p>{{ __('laranail/waitlist-kit::admin.forget_hint') }}</p>
            <form method="post" action="{{ route('laranail-waitlist-kit.admin.forget') }}" class="wka-form wka-inline">
                @csrf
                @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'email', 'type' => 'email', 'label' => __('laranail/waitlist-kit::admin.email'), 'value' => ''])
                <button type="submit" class="wka-button wka-button-danger">{{ __('laranail/waitlist-kit::admin.erase') }}</button>
            </form>
        </section>
    @endcan
@endsection
