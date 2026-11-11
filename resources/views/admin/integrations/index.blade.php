@extends('laranail/waitlist-kit::admin.layout', ['title' => __('laranail/waitlist-kit::admin.nav.integrations')])

@section('content')
    @can(\Simtabi\Laranail\WaitlistKit\Auth\Abilities::MANAGE)
        <p><a class="wka-button" href="{{ route('laranail-waitlist-kit.admin.integrations.create') }}">{{ __('laranail/waitlist-kit::admin.new_integration') }}</a></p>
    @endcan
    <table class="wka-table">
        <caption>{{ __('laranail/waitlist-kit::admin.nav.integrations') }}</caption>
        <thead><tr>
            <th scope="col">{{ __('laranail/waitlist-kit::admin.driver') }}</th>
            <th scope="col">{{ __('laranail/waitlist-kit::admin.serves') }}</th>
            <th scope="col">{{ __('laranail/waitlist-kit::admin.enabled') }}</th>
            <th scope="col"><span class="wka-sr">{{ __('laranail/waitlist-kit::admin.actions') }}</span></th>
        </tr></thead>
        <tbody>
        @forelse ($integrations as $integration)
            <tr>
                <th scope="row"><a href="{{ route('laranail-waitlist-kit.admin.integrations.edit', $integration) }}">{{ $integration->driver }}</a></th>
                <td>{{ $integration->integrable?->name ?? __('laranail/waitlist-kit::admin.every_campaign') }}</td>
                <td>{{ $integration->enabled ? __('laranail/waitlist-kit::admin.yes') : __('laranail/waitlist-kit::admin.no') }}</td>
                <td>
                    @can(\Simtabi\Laranail\WaitlistKit\Auth\Abilities::MANAGE)
                        <form method="post" action="{{ route('laranail-waitlist-kit.admin.integrations.destroy', $integration) }}" x-data="wkConfirm" x-on:submit="check" data-message="{{ __('laranail/waitlist-kit::admin.confirm_remove') }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="wka-link wka-danger-text">{{ __('laranail/waitlist-kit::admin.remove') }}<span class="wka-sr"> {{ $integration->driver }}</span></button>
                        </form>
                    @endcan
                </td>
            </tr>
        @empty
            <tr><td colspan="4">{{ __('laranail/waitlist-kit::admin.none') }}</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection
