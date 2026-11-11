{{ __('laranail/waitlist-kit::mail.greeting') }}

{{ __('laranail/waitlist-kit::mail.invite_body', ['site' => $site]) }}

{{ $link }}

{{ __('laranail/waitlist-kit::mail.signoff', ['site' => $site]) }}
@if ($unsubscribeUrl)

{{ __('laranail/waitlist-kit::mail.unsubscribe', ['url' => $unsubscribeUrl]) }}
@endif
