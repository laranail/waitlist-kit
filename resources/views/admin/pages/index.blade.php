@extends('laranail/waitlist-kit::admin.layout', ['title' => __('laranail/waitlist-kit::admin.nav.pages'), 'pretitle' => __('laranail/waitlist-kit::admin.content')])

@section('content')
    <div class="card">
        @if ($pages->isEmpty())
            <div class="card-body">
                @include('laranail/waitlist-kit::admin.partials.empty', ['title' => __('laranail/waitlist-kit::admin.empty.pages'), 'hint' => __('laranail/waitlist-kit::admin.empty.pages_hint')])
            </div>
        @else
            <div class="table-responsive" tabindex="0" role="region" aria-labelledby="wka-pages-index-table">
                <table class="table table-vcenter card-table">
                    <caption class="visually-hidden" id="wka-pages-index-table">{{ __('laranail/waitlist-kit::admin.nav.pages') }}</caption>
                    <thead><tr>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.title') }}</th>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.slug') }}</th>
                        <th scope="col">{{ __('laranail/waitlist-kit::admin.status') }}</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($pages as $page)
                        <tr>
                            <th scope="row" class="fw-normal"><a href="{{ route('laranail-waitlist-kit.admin.pages.edit', $page) }}">{{ $page->titleFor(app()->getLocale()) }}</a></th>
                            <td><code>{{ $page->slug }}</code></td>
                            <td>@include('laranail/waitlist-kit::admin.partials.status', ['status' => $page->status])</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
