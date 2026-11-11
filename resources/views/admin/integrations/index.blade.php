@extends('laranail/waitlist-kit::admin.layout', ['title' => __('laranail/waitlist-kit::admin.nav.integrations'), 'pretitle' => __('laranail/waitlist-kit::admin.manage')])

@section('actions')
    @can(\Simtabi\Laranail\WaitlistKit\Auth\Abilities::MANAGE)
        <a class="btn btn-primary" href="{{ route('laranail-waitlist-kit.admin.integrations.create') }}">@include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'plus']) {{ __('laranail/waitlist-kit::admin.new_integration') }}</a>
    @endcan
@endsection

@section('content')
    <div class="card">
        @if ($integrations->isEmpty())
            <div class="card-body">
                @include('laranail/waitlist-kit::admin.partials.empty', ['title' => __('laranail/waitlist-kit::admin.empty.integrations'), 'hint' => __('laranail/waitlist-kit::admin.empty.integrations_hint')])
            </div>
        @else
            <div class="table-responsive" tabindex="0" role="region" aria-labelledby="wka-integrations-index-table">
                <table class="table table-vcenter card-table">
                    <caption class="visually-hidden" id="wka-integrations-index-table">{{ __('laranail/waitlist-kit::admin.nav.integrations') }}</caption>
                    <thead><tr>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.driver') }}</th>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.serves') }}</th>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.enabled') }}</th>
                        <th scope="col" class="w-1"><span class="visually-hidden">{{ __('laranail/waitlist-kit::admin.actions') }}</span></th>
                    </tr></thead>
                    <tbody>
                    @foreach ($integrations as $integration)
                        <tr>
                            <th scope="row" class="fw-normal"><a href="{{ route('laranail-waitlist-kit.admin.integrations.edit', $integration) }}">{{ $integration->driver }}</a></th>
                            <td>{{ $integration->integrable?->name ?? __('laranail/waitlist-kit::admin.every_campaign') }}</td>
                            <td>
                                @if ($integration->enabled)
                                    <span class="badge bg-green-lt">{{ __('laranail/waitlist-kit::admin.yes') }}</span>
                                @else
                                    <span class="badge bg-secondary-lt">{{ __('laranail/waitlist-kit::admin.no') }}</span>
                                @endif
                            </td>
                            <td>
                                @can(\Simtabi\Laranail\WaitlistKit\Auth\Abilities::MANAGE)
                                    <form method="post" action="{{ route('laranail-waitlist-kit.admin.integrations.destroy', $integration) }}" x-data="wkConfirm" x-on:submit="check" data-message="{{ __('laranail/waitlist-kit::admin.confirm_remove') }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-ghost-danger">@include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'trash']) {{ __('laranail/waitlist-kit::admin.remove') }}<span class="visually-hidden"> {{ $integration->driver }}</span></button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
