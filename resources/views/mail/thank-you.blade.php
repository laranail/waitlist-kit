{{ __('laranail/waitlist-kit::mail.greeting') }}

{{ __('laranail/waitlist-kit::mail.body', ['site' => $site]) }}

{{ __('laranail/waitlist-kit::mail.next') }}
@if ($statusUrl)

{{ __('laranail/waitlist-kit::mail.status', ['url' => $statusUrl]) }}
@endif

{{ __('laranail/waitlist-kit::mail.signoff', ['site' => $site]) }}
@if ($unsubscribeUrl)

{{ __('laranail/waitlist-kit::mail.unsubscribe', ['url' => $unsubscribeUrl]) }}
@endif
