{{-- A campaign's or a page's blocks, edited one locale at a time. $owner, $store, $locales, $locale, $blocks, $blockTypes.
     Each block is a native <details> (no script) holding its own form; AdminForm keeps every form's ids apart. --}}
@php($form = \Simtabi\Laranail\WaitlistKit\Support\AdminForm::class)
<section class="card" id="blocks" aria-labelledby="blocks-title">
    <div class="card-header">
        <h2 class="card-title" id="blocks-title">{{ __('laranail/waitlist-kit::admin.blocks') }}</h2>
        @if (count($locales) > 1)
            <nav class="card-actions" aria-label="{{ __('laranail/waitlist-kit::admin.locale') }}">
                <ul class="nav nav-pills">
                    @foreach ($locales as $code)
                        <li class="nav-item"><a class="nav-link @if ($code === $locale) active @endif" href="?locale={{ $code }}#blocks" @if ($code === $locale) aria-current="true" @endif>{{ $code }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @endif
    </div>

    <div class="list-group list-group-flush">
        @foreach ($blocks as $block)
            @php($type = \Simtabi\Laranail\WaitlistKit\Enums\BlockType::tryFrom($block->type))
            @continue(! $type)
            @php($values = \Simtabi\Laranail\WaitlistKit\Validation\BlockFields::toInput($type, $block->content[$locale] ?? []))
            <details class="list-group-item wka-block p-0" @if ($errors->any() && old('block') === $block->id) open @endif>
                <summary class="d-flex align-items-center gap-2 px-3 py-2">
                    <span class="badge bg-secondary-lt">{{ $loop->iteration }}</span>
                    <strong>{{ str_replace('_', ' ', ucfirst($type->value)) }}</strong>
                    @unless ($block->visible)<span class="badge bg-yellow-lt">{{ __('laranail/waitlist-kit::admin.hidden') }}</span>@endunless
                    <span class="ms-auto wka-chevron">@include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'down'])</span>
                </summary>
                <div class="p-3">
                    <form method="post" action="{{ route('laranail-waitlist-kit.admin.blocks.update', $block) }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="type" value="{{ $type->value }}">
                        <input type="hidden" name="locale" value="{{ $locale }}">
                        <input type="hidden" name="block" value="{{ $block->id }}">
                        @foreach (\Simtabi\Laranail\WaitlistKit\Validation\BlockFields::for($type) as $field => $spec)
                            @include('laranail/waitlist-kit::admin.partials.field', [
                                'name'     => $field,
                                'label'    => __('laranail/waitlist-kit::admin.fields.' . $field),
                                'type'     => $spec['kind'] === 'text' ? 'text' : 'textarea',
                                'rows'     => $spec['kind'] === 'text' ? 1 : 6,
                                'value'    => $values[$field],
                                'idPrefix' => $form::block($block->id),
                                'hint'     => match ($spec['kind']) { 'pairs' => __('laranail/waitlist-kit::admin.pairs_hint'), 'lines' => __('laranail/waitlist-kit::admin.lines_hint'), default => null },
                            ])
                        @endforeach
                        @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'visible', 'idPrefix' => $form::block($block->id), 'type' => 'checkbox', 'label' => __('laranail/waitlist-kit::admin.visible'), 'value' => $block->visible])
                        <button type="submit" class="btn btn-primary">{{ __('laranail/waitlist-kit::admin.save') }}</button>
                    </form>
                    <div class="btn-list mt-3 pt-3 border-top">
                        <form method="post" action="{{ route('laranail-waitlist-kit.admin.blocks.move', $block) }}">@csrf<input type="hidden" name="direction" value="up"><input type="hidden" name="locale" value="{{ $locale }}"><button type="submit" class="btn btn-sm btn-ghost-secondary" @disabled($loop->first)>@include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'up']) {{ __('laranail/waitlist-kit::admin.move_up') }}</button></form>
                        <form method="post" action="{{ route('laranail-waitlist-kit.admin.blocks.move', $block) }}">@csrf<input type="hidden" name="direction" value="down"><input type="hidden" name="locale" value="{{ $locale }}"><button type="submit" class="btn btn-sm btn-ghost-secondary" @disabled($loop->last)>@include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'down']) {{ __('laranail/waitlist-kit::admin.move_down') }}</button></form>
                        <form method="post" action="{{ route('laranail-waitlist-kit.admin.blocks.destroy', $block) }}" x-data="wkConfirm" x-on:submit="check" data-message="{{ __('laranail/waitlist-kit::admin.confirm_remove') }}">@csrf @method('DELETE')<input type="hidden" name="locale" value="{{ $locale }}"><button type="submit" class="btn btn-sm btn-ghost-danger">@include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'trash']) {{ __('laranail/waitlist-kit::admin.remove') }}</button></form>
                    </div>
                </div>
            </details>
        @endforeach
    </div>

    <div class="card-footer">
        <form method="post" action="{{ $store }}" class="row g-2 align-items-end">
            @csrf
            <input type="hidden" name="locale" value="{{ $locale }}">
            <input type="hidden" name="_form" value="{{ $form::ADD_BLOCK }}">
            @include('laranail/waitlist-kit::admin.partials.field', ['name' => 'type', 'idPrefix' => $form::ADD_BLOCK, 'type' => 'select', 'label' => __('laranail/waitlist-kit::admin.add_block'), 'value' => '', 'wrapperClass' => 'col-sm-auto', 'options' => collect($blockTypes)->mapWithKeys(fn ($t) => [$t->value => str_replace('_', ' ', ucfirst($t->value))])->all()])
            <div class="col-sm-auto"><button type="submit" class="btn btn-outline-primary">@include('laranail/waitlist-kit::admin.partials.icon', ['name' => 'plus']) {{ __('laranail/waitlist-kit::admin.add') }}</button></div>
        </form>
    </div>
</section>
