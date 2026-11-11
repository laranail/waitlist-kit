{{-- Tabler's empty state, inside a card body. $title, $hint, and optionally $actionUrl with $actionLabel. --}}
<div class="empty">
    <p class="empty-title">{{ $title }}</p>
    <p class="empty-subtitle text-secondary">{{ $hint }}</p>
    @isset($actionUrl)
        <div class="empty-action">
            <a href="{{ $actionUrl }}" class="btn btn-primary">@include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'plus']) {{ $actionLabel }}</a>
        </div>
    @endisset
</div>
