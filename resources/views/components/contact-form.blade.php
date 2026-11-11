@if (session(\Simtabi\Laranail\WaitlistKit\Http\Controllers\ContactController::FLASH))
    <p data-scope="contact-form" data-part="status" data-state="sent" role="status">{{ __('laranail/waitlist-kit::contact.sent') }}</p>
@else
    <form method="post" action="{{ route('laranail-waitlist-kit.contact.store') }}" novalidate
          {{ $attributes->merge(['data-scope' => 'contact-form']) }}>
        @csrf
        <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
        <input type="hidden" name="return" value="{{ $return ?? request()->getRequestUri() }}">
        @if ($campaign)<input type="hidden" name="campaign" value="{{ $campaign }}">@endif

        @foreach (array_filter(['name' => 'text', 'email' => 'email', 'company' => $company === 'off' ? null : 'text']) as $field => $type)
            <div data-part="field" data-field="{{ $field }}" @error($field) data-state="invalid" @enderror>
                <label data-part="label" for="contact-{{ $field }}">{{ __('laranail/waitlist-kit::contact.' . $field) }}</label>
                <input data-part="input" id="contact-{{ $field }}" type="{{ $type }}" name="{{ $field }}" value="{{ old($field) }}"
                       @if ($field !== 'company' || $company === 'required') required @endif
                       maxlength="{{ $field === 'email' ? 254 : ($field === 'company' ? 160 : 120) }}"
                       autocomplete="{{ ['name' => 'name', 'email' => 'email', 'company' => 'organization'][$field] }}"
                       @error($field) aria-invalid="true" aria-describedby="contact-{{ $field }}-error" @enderror>
                @error($field)<p data-part="error" id="contact-{{ $field }}-error">{{ $message }}</p>@enderror
            </div>
        @endforeach

        <div data-part="field" data-field="topic">
            <label data-part="label" for="contact-topic">{{ __('laranail/waitlist-kit::contact.topic') }}</label>
            <select data-part="input" id="contact-topic" name="topic" required>
                @foreach ($topics as $topic)
                    <option value="{{ $topic }}" @selected(old('topic', $topics[0]) === $topic)>{{ __('laranail/waitlist-kit::contact.topics.' . $topic) === 'laranail/waitlist-kit::contact.topics.' . $topic ? ucfirst($topic) : __('laranail/waitlist-kit::contact.topics.' . $topic) }}</option>
                @endforeach
            </select>
        </div>

        <div data-part="field" data-field="message" @error('message') data-state="invalid" @enderror>
            <label data-part="label" for="contact-message">{{ __('laranail/waitlist-kit::contact.message') }}</label>
            <textarea data-part="input" id="contact-message" name="message" required minlength="10" maxlength="2000"
                      @error('message') aria-invalid="true" aria-describedby="contact-message-error" @enderror>{{ old('message') }}</textarea>
            @error('message')<p data-part="error" id="contact-message-error">{{ $message }}</p>@enderror
        </div>

        @if ($consent)
            <div data-part="field" data-field="agree" @error('agree') data-state="invalid" @enderror>
                <input data-part="input" id="contact-agree" type="checkbox" name="agree" value="1" required @checked(old('agree'))>
                <label data-part="label" for="contact-agree">
                    @if ($privacy)
                        {!! __('laranail/waitlist-kit::contact.agree_linked', ['link' => '<a href="' . e($privacy) . '">' . e(__('laranail/waitlist-kit::legal.privacy')) . '</a>']) !!}
                    @else
                        {{ __('laranail/waitlist-kit::contact.agree') }}
                    @endif
                </label>
                @error('agree')<p data-part="error">{{ $message }}</p>@enderror
            </div>
        @endif

        <div hidden aria-hidden="true">
            <label for="contact-{{ $honeypot }}">{{ $honeypot }}</label>
            <input id="contact-{{ $honeypot }}" type="text" name="{{ $honeypot }}" tabindex="-1" autocomplete="off">
        </div>

        @if ($captcha)
            <x-dynamic-component component="laranail-captcha::captcha" />
            @error('captcha')<p data-part="error">{{ $message }}</p>@enderror
        @endif

        <button data-part="submit" type="submit">{{ __('laranail/waitlist-kit::contact.send') }}</button>
    </form>
@endif
