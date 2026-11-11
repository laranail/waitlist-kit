{{-- A status as a Tabler badge: the word carries the meaning, the tint only repeats it. $status is a backed enum. --}}
@php
    $value = $status->value;
    $tint = match ($value) {
        'live', 'published', 'confirmed', 'converted' => 'green',
        'launched', 'invited' => 'azure',
        'pending' => 'yellow',
        'closed', 'unsubscribed' => 'red',
        default => 'secondary',
    };
@endphp
<span class="badge bg-{{ $tint }}-lt">{{ __('laranail/waitlist-kit::admin.statuses.' . $value) }}</span>
