@if (session(\Simtabi\Laranail\WaitlistKit\Http\Controllers\SignupController::FLASH))
    <p role="status">{{ __('laranail/waitlist-kit::page.sent') }}</p>
@else
    <form method="post" action="{{ route('laranail-waitlist-kit.campaign.signup', $campaign) }}" novalidate>
        @csrf
        <input type="hidden" name="locale" value="{{ $locale }}">

        <label for="waitlist-email">{{ $label ?? __('laranail/waitlist-kit::page.email') }}</label>
        <input id="waitlist-email" type="email" name="email" value="{{ old('email') }}" required maxlength="254"
               autocomplete="email" @error('email') aria-invalid="true" aria-describedby="waitlist-email-error" @enderror>
        @error('email')<p id="waitlist-email-error">{{ $message }}</p>@enderror

        {{-- The honeypot: hidden from people and from assistive technology; only a script fills it in. --}}
        <div hidden aria-hidden="true">
            <label for="waitlist-{{ $honeypot }}">{{ $honeypot }}</label>
            <input id="waitlist-{{ $honeypot }}" type="text" name="{{ $honeypot }}" tabindex="-1" autocomplete="off">
        </div>

        @if ($captcha)
            {{-- Resolved only when rendered, so the page compiles without laranail/captcha installed. --}}
            <x-dynamic-component component="laranail-captcha::captcha" />
            @error('captcha')<p>{{ $message }}</p>@enderror
        @endif

        <button type="submit">{{ $button ?? __('laranail/waitlist-kit::page.join') }}</button>
    </form>
@endif
