@extends('laranail/waitlist-kit::admin.layout', ['title' => $page->titleFor($locale)])

@section('content')
    @if ($page->isPublished())
        <p><a href="{{ route('laranail-waitlist-kit.page.show', $page) }}">{{ __('laranail/waitlist-kit::admin.view_page') }}</a></p>
    @else
        <p class="wka-hint">{{ __('laranail/waitlist-kit::admin.draft_hint') }}</p>
    @endif

    <section class="wka-panel" aria-labelledby="details">
        <h2 id="details">{{ __('laranail/waitlist-kit::admin.details') }}</h2>
        <form method="post" action="{{ route('laranail-waitlist-kit.admin.pages.update', $page) }}" class="wka-form">
            @csrf @method('PUT')
            <input type="hidden" name="locale" value="{{ $locale }}">
            @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'title', 'label' => __('laranail/waitlist-kit::admin.title') . ' (' . $locale . ')', 'value' => $page->title[$locale] ?? '', 'required' => true, 'maxlength' => 160])
            @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'status', 'type' => 'select', 'label' => __('laranail/waitlist-kit::admin.status'), 'value' => $page->status->value, 'options' => collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->value])->all()])
            <button type="submit" class="wka-button">{{ __('laranail/waitlist-kit::admin.save') }}</button>
        </form>
    </section>

    @include('laranail/waitlist-kit::admin.partials.blocks', ['owner' => $page, 'store' => route('laranail-waitlist-kit.admin.pages.blocks.store', $page), 'locales' => [$locale]])
@endsection
