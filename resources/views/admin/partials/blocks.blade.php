{{-- A campaign's or a page's blocks, edited one locale at a time. $owner, $store, $locales, $locale, $blocks, $blockTypes. --}}
<section class="wka-panel" id="blocks" aria-labelledby="blocks-title">
    <h2 id="blocks-title">{{ __('laranail/waitlist-kit::admin.blocks') }}</h2>

    @if (count($locales) > 1)
        <nav aria-label="{{ __('laranail/waitlist-kit::admin.locale') }}">
            <ul class="wka-tabs">
                @foreach ($locales as $code)
                    <li><a href="?locale={{ $code }}#blocks" @if ($code === $locale) aria-current="true" @endif>{{ $code }}</a></li>
                @endforeach
            </ul>
        </nav>
    @endif

    @foreach ($blocks as $block)
        @php($type = \Simtabi\Laranail\WaitlistKit\Enums\BlockType::tryFrom($block->type))
        @continue(! $type)
        @php($values = \Simtabi\Laranail\WaitlistKit\Validation\BlockFields::toInput($type, $block->content[$locale] ?? []))
        <details class="wka-block" @if ($errors->any() && old('block') === $block->id) open @endif>
            <summary><strong>{{ $loop->iteration }}. {{ $type->value }}</strong> @unless ($block->visible) <span class="wka-tag">{{ __('laranail/waitlist-kit::admin.hidden') }}</span> @endunless</summary>
            <form method="post" action="{{ route('laranail-waitlist-kit.admin.blocks.update', $block) }}" class="wka-form">
                @csrf @method('PUT')
                <input type="hidden" name="type" value="{{ $type->value }}">
                <input type="hidden" name="locale" value="{{ $locale }}">
                <input type="hidden" name="block" value="{{ $block->id }}">
                @foreach (\Simtabi\Laranail\WaitlistKit\Validation\BlockFields::for($type) as $field => $spec)
                    @include('laranail/waitlist-kit::admin.partials.field', [
                        'name'  => $field,
                        'label' => __('laranail/waitlist-kit::admin.fields.' . $field),
                        'type'  => $spec['kind'] === 'text' ? 'text' : 'textarea',
                        'rows'  => $spec['kind'] === 'text' ? 1 : 6,
                        'value' => $values[$field],
                        'idPrefix' => \Simtabi\Laranail\WaitlistKit\Support\AdminForm::block($block->id),
                        'hint'  => match ($spec['kind']) { 'pairs' => __('laranail/waitlist-kit::admin.pairs_hint'), 'lines' => __('laranail/waitlist-kit::admin.lines_hint'), default => null },
                    ])
                @endforeach
                @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'visible', 'idPrefix' => \Simtabi\Laranail\WaitlistKit\Support\AdminForm::block($block->id), 'type' => 'checkbox', 'label' => __('laranail/waitlist-kit::admin.visible'), 'value' => $block->visible])
                <button type="submit" class="wka-button">{{ __('laranail/waitlist-kit::admin.save') }}</button>
            </form>
            <div class="wka-actions">
                <form method="post" action="{{ route('laranail-waitlist-kit.admin.blocks.move', $block) }}">@csrf<input type="hidden" name="direction" value="up"><input type="hidden" name="locale" value="{{ $locale }}"><button type="submit" class="wka-link" @disabled($loop->first)>{{ __('laranail/waitlist-kit::admin.move_up') }}</button></form>
                <form method="post" action="{{ route('laranail-waitlist-kit.admin.blocks.move', $block) }}">@csrf<input type="hidden" name="direction" value="down"><input type="hidden" name="locale" value="{{ $locale }}"><button type="submit" class="wka-link" @disabled($loop->last)>{{ __('laranail/waitlist-kit::admin.move_down') }}</button></form>
                <form method="post" action="{{ route('laranail-waitlist-kit.admin.blocks.destroy', $block) }}" x-data="wkConfirm" x-on:submit="check" data-message="{{ __('laranail/waitlist-kit::admin.confirm_remove') }}">@csrf @method('DELETE')<input type="hidden" name="locale" value="{{ $locale }}"><button type="submit" class="wka-link wka-danger-text">{{ __('laranail/waitlist-kit::admin.remove') }}</button></form>
            </div>
        </details>
    @endforeach

    <form method="post" action="{{ $store }}" class="wka-form wka-inline">
        @csrf
        <input type="hidden" name="locale" value="{{ $locale }}">
        <input type="hidden" name="_form" value="{{ \Simtabi\Laranail\WaitlistKit\Support\AdminForm::ADD_BLOCK }}">
        @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'type', 'idPrefix' => \Simtabi\Laranail\WaitlistKit\Support\AdminForm::ADD_BLOCK, 'type' => 'select', 'label' => __('laranail/waitlist-kit::admin.add_block'), 'value' => '', 'options' => collect($blockTypes)->mapWithKeys(fn ($t) => [$t->value => $t->value])->all()])
        <button type="submit" class="wka-button">{{ __('laranail/waitlist-kit::admin.add') }}</button>
    </form>
</section>
