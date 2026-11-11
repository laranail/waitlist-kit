@extends('laranail/waitlist-kit::admin.layout', ['title' => $page->titleFor($locale), 'pretitle' => __('laranail/waitlist-kit::admin.nav.pages')])

@section('actions')
    @if ($page->isPublished())
        <a class="btn btn-outline-secondary" href="{{ route('laranail-waitlist-kit.page.show', $page) }}">@include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'external']) {{ __('laranail/waitlist-kit::admin.view_page') }}</a>
    @endif
@endsection

@section('content')
    @unless ($page->isPublished())
        <div class="alert alert-info" role="status">{{ __('laranail/waitlist-kit::admin.draft_hint') }}</div>
    @endunless

    <div class="row row-cards">
        <div class="col-lg-5">
            <form method="post" action="{{ route('laranail-waitlist-kit.admin.pages.update', $page) }}" class="card" aria-labelledby="details">
                @csrf @method('PUT')
                <input type="hidden" name="locale" value="{{ $locale }}">
                <div class="card-header"><h2 class="card-title" id="details">{{ __('laranail/waitlist-kit::admin.details') }}</h2></div>
                <div class="card-body">
                    @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'title', 'label' => __('laranail/waitlist-kit::admin.title') . ' (' . $locale . ')', 'value' => $page->title[$locale] ?? '', 'required' => true, 'maxlength' => 160])
                    @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'status', 'type' => 'select', 'label' => __('laranail/waitlist-kit::admin.status'), 'value' => $page->status->value, 'wrapperClass' => 'mb-0', 'options' => collect($statuses)->mapWithKeys(fn ($s) => [$s->value => __('laranail/waitlist-kit::admin.statuses.' . $s->value)])->all()])
                </div>
                <div class="card-footer text-end"><button type="submit" class="btn btn-primary">{{ __('laranail/waitlist-kit::admin.save') }}</button></div>
            </form>
        </div>
        <div class="col-lg-7">
            @include('laranail/waitlist-kit::admin.partials.blocks', ['owner' => $page, 'store' => route('laranail-waitlist-kit.admin.pages.blocks.store', $page), 'locales' => [$locale]])
        </div>
    </div>
@endsection
