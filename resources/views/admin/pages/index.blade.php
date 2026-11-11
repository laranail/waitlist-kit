@extends('laranail/waitlist-kit::admin.layout', ['title' => __('laranail/waitlist-kit::admin.nav.pages')])

@section('content')
    <table class="wka-table">
        <caption>{{ __('laranail/waitlist-kit::admin.nav.pages') }}</caption>
        <thead><tr>
            <th scope="col">{{ __('laranail/waitlist-kit::admin.title') }}</th>
            <th scope="col">{{ __('laranail/waitlist-kit::admin.slug') }}</th>
            <th scope="col">{{ __('laranail/waitlist-kit::admin.status') }}</th>
        </tr></thead>
        <tbody>
        @forelse ($pages as $page)
            <tr>
                <th scope="row"><a href="{{ route('laranail-waitlist-kit.admin.pages.edit', $page) }}">{{ $page->titleFor(app()->getLocale()) }}</a></th>
                <td><code>{{ $page->slug }}</code></td>
                <td>{{ $page->status->value }}</td>
            </tr>
        @empty
            <tr><td colspan="3">{{ __('laranail/waitlist-kit::admin.none') }}</td></tr>
        @endforelse
        </tbody>
    </table>
@endsection
