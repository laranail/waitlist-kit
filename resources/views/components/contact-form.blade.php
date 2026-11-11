@if (session(\Simtabi\Laranail\WaitlistKit\Http\Controllers\ContactController::FLASH))
    <p data-scope="contact-form" data-part="status" data-state="sent" role="status">{{ __('laranail/waitlist-kit::contact.sent') }}</p>
@else
    <form method="post" action="{{ route('laranail-waitlist-kit.contact.store') }}" novalidate
          {{ $attributes->merge(['data-scope' => 'contact-form']) }}>
        @csrf
        {{-- After a reload with errors, the first rejected control takes focus, so its error is read out with it. --}}
        @php
            $firstError = collect(['name', 'email', 'company', 'message', 'agree'])->first(fn ($field) => $errors->contact->has($field));
            // Old input belongs to this form only when this form was the one rejected; the sign-up form shares `email`.
            $old = fn (string $field, mixed $default = null): mixed => $errors->contact->any() ? old($field, $default) : $default;
        @endphp
        <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
        <input type="hidden" name="return" value="{{ $return ?? request()->getRequestUri() }}">
        @if ($campaign)<input type="hidden" name="campaign" value="{{ $campaign }}">@endif

        @foreach (array_filter(['name' => 'text', 'email' => 'email', 'company' => $company === 'off' ? null : 'text']) as $field => $type)
            <div data-part="field" data-field="{{ $field }}" @error($field, 'contact') data-state="invalid" @enderror>
                <label data-part="label" for="contact-{{ $field }}">{{ __('laranail/waitlist-kit::contact.' . $field) }}</label>
                <input data-part="input" id="contact-{{ $field }}" type="{{ $type }}" name="{{ $field }}" value="{{ $old($field) }}"
                       @if ($field !== 'company' || $company === 'required') required @endif
                       maxlength="{{ $field === 'email' ? 254 : ($field === 'company' ? 160 : 120) }}"
                       autocomplete="{{ ['name' => 'name', 'email' => 'email', 'company' => 'organization'][$field] }}"
                       @error($field, 'contact') aria-invalid="true" aria-describedby="contact-{{ $field }}-error" @enderror @if ($firstError === $field) autofocus @endif>
                <p data-part="error" data-for="{{ $field }}" id="contact-{{ $field }}-error" @unless ($errors->contact->has($field)) hidden @endunless>{{ $errors->contact->first($field) }}</p>
            </div>
        @endforeach

        <div data-part="field" data-field="topic">
            <label data-part="label" for="contact-topic">{{ __('laranail/waitlist-kit::contact.topic') }}</label>
            <select data-part="input" id="contact-topic" name="topic" required>
                @foreach ($topics as $topic)
                    <option value="{{ $topic }}" @selected($old('topic', $topics[0]) === $topic)>{{ __('laranail/waitlist-kit::contact.topics.' . $topic) === 'laranail/waitlist-kit::contact.topics.' . $topic ? ucfirst($topic) : __('laranail/waitlist-kit::contact.topics.' . $topic) }}</option>
                @endforeach
            </select>
        </div>

        <div data-part="field" data-field="message" @error('message', 'contact') data-state="invalid" @enderror>
            <label data-part="label" for="contact-message">{{ __('laranail/waitlist-kit::contact.message') }}</label>
            <textarea data-part="input" id="contact-message" name="message" required minlength="10" maxlength="2000"
                      @error('message', 'contact') aria-invalid="true" aria-describedby="contact-message-error" @enderror @if ($firstError === 'message') autofocus @endif>{{ $old('message') }}</textarea>
            <p data-part="error" data-for="message" id="contact-message-error" @unless ($errors->contact->has('message')) hidden @endunless>{{ $errors->contact->first('message') }}</p>
        </div>

        @if ($consent)
            <div data-part="field" data-field="agree" @error('agree', 'contact') data-state="invalid" @enderror>
                <input data-part="input" id="contact-agree" type="checkbox" name="agree" value="1" required @checked($old('agree'))
                       @error('agree', 'contact') aria-invalid="true" aria-describedby="contact-agree-error" @enderror @if ($firstError === 'agree') autofocus @endif>
                <label data-part="label" for="contact-agree">
                    @if ($privacy)
                        {!! __('laranail/waitlist-kit::contact.agree_linked', ['link' => '<a href="' . e($privacy) . '">' . e(__('laranail/waitlist-kit::legal.privacy')) . '</a>']) !!}
                    @else
                        {{ __('laranail/waitlist-kit::contact.agree') }}
                    @endif
                </label>
                <p data-part="error" data-for="agree" id="contact-agree-error" @unless ($errors->contact->has('agree')) hidden @endunless>{{ $errors->contact->first('agree') }}</p>
            </div>
        @endif

        <div hidden aria-hidden="true">
            <label for="contact-{{ $honeypot }}">{{ $honeypot }}</label>
            <input id="contact-{{ $honeypot }}" type="text" name="{{ $honeypot }}" tabindex="-1" autocomplete="off">
        </div>

        @if ($captcha)
            <x-dynamic-component component="laranail-captcha::captcha" />
            @error('captcha', 'contact')<p data-part="error" data-for="captcha" id="contact-captcha-error" role="alert">{{ $message }}</p>@enderror
        @endif

        <button data-part="submit" type="submit">{{ __('laranail/waitlist-kit::contact.send') }}</button>
        <p data-part="status" role="status" tabindex="-1" hidden></p>
    </form>
@endif
