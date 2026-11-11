{{-- Early access: someone holding an invitation enters their code and is sent on to the product. --}}
@php($accessError = $errors->getBag('access')->first('code'))
<section class="wk-block wk-access" data-block="access" aria-labelledby="wk-access-title">
    <h2 class="wk-heading" id="wk-access-title">{{ __('laranail/waitlist-kit::page.access_title') }}</h2>
    <form class="wk-form" method="post" action="{{ route('laranail-waitlist-kit.campaign.access', $campaign) }}" novalidate data-scope="access-form">
        @csrf
        <div class="wk-field" data-part="field" data-field="code" @if ($accessError !== '') data-state="invalid" @endif>
            <label class="wk-label" data-part="label" for="access-code">{{ __('laranail/waitlist-kit::page.access_label') }}</label>
            <input class="wk-input" data-part="input" id="access-code" type="text" name="code" required maxlength="32" autocomplete="off" spellcheck="false"
                   @if ($accessError !== '') aria-invalid="true" aria-describedby="access-code-error" autofocus @endif>
            <p class="wk-error" data-part="error" data-for="code" id="access-code-error" @if ($accessError === '') hidden @endif>{{ $accessError }}</p>
        </div>
        <button class="wk-button" data-part="submit" type="submit">{{ __('laranail/waitlist-kit::page.access_button') }}</button>
    </form>
</section>
