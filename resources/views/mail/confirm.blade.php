{{ __('laranail/waitlist-kit::mail.greeting') }}

{{ __('laranail/waitlist-kit::mail.confirm_body', ['site' => $site]) }}

{{ $link }}

{{ trans_choice('laranail/waitlist-kit::mail.confirm_expires', $days, ['days' => $days]) }}

{{ __('laranail/waitlist-kit::mail.confirm_ignore') }}

{{ __('laranail/waitlist-kit::mail.signoff', ['site' => $site]) }}
@if ($unsubscribeUrl)

{{ __('laranail/waitlist-kit::mail.unsubscribe', ['url' => $unsubscribeUrl]) }}
@endif
