{{-- The sign-up form. Works without JavaScript; with the kit's script, wkForm posts it in place and shows the answer. --}}
@if (session(\Simtabi\Laranail\WaitlistKit\Http\Controllers\SignupController::FLASH))
    <p class="wk-status" data-scope="signup-form" data-part="status" data-state="sent" role="status">{{ __('laranail/waitlist-kit::page.sent') }}</p>
@else
    <form class="wk-form" method="post" action="{{ route('laranail-waitlist-kit.campaign.signup', $campaign) }}" novalidate
          data-scope="signup-form" x-data="wkForm" x-on:submit="submit">
        @csrf
        <input type="hidden" name="locale" value="{{ $locale }}">

        <div class="wk-field" data-part="field" data-field="email" @error('email') data-state="invalid" @enderror>
            <label class="wk-label" data-part="label" for="waitlist-email">{{ $label ?? __('laranail/waitlist-kit::page.email') }}</label>
            <input class="wk-input" data-part="input" id="waitlist-email" type="email" name="email" value="{{ old('email') }}" required maxlength="254"
                   autocomplete="email" @error('email') aria-invalid="true" aria-describedby="waitlist-email-error" @enderror>
            <p class="wk-error" data-part="error" data-for="email" id="waitlist-email-error" @unless ($errors->has('email')) hidden @endunless>{{ $errors->first('email') }}</p>
        </div>

        {{-- The honeypot: hidden from people and from assistive technology; only a script fills it in. --}}
        <div hidden aria-hidden="true">
            <label for="waitlist-{{ $honeypot }}">{{ $honeypot }}</label>
            <input id="waitlist-{{ $honeypot }}" type="text" name="{{ $honeypot }}" tabindex="-1" autocomplete="off">
        </div>

        @if ($captcha)
            {{-- Resolved only when rendered, so the page compiles without laranail/captcha installed. --}}
            <x-dynamic-component component="laranail-captcha::captcha" />
            @error('captcha')<p class="wk-error" data-part="error">{{ $message }}</p>@enderror
        @endif

        @if ($privacy)
            <p class="wk-consent" data-part="consent">{!! __('laranail/waitlist-kit::page.consent_linked', ['link' => '<a href="' . e($privacy) . '">' . e(__('laranail/waitlist-kit::legal.privacy')) . '</a>']) !!}</p>
        @endif

        <button class="wk-button" data-part="submit" type="submit">{{ $button ?? __('laranail/waitlist-kit::page.join') }}</button>
        <p class="wk-status" data-part="status" role="status" hidden></p>
    </form>
@endif
