<section class="wk-block wk-features" data-block="features">
    @isset($content['title'])<h2 class="wk-heading">{{ $content['title'] }}</h2>@endisset
    <ul class="wk-feature-list">
        @foreach ((array) ($content['items'] ?? []) as $item)
            <li class="wk-feature">
                <h3 class="wk-feature-title">{{ $item['title'] ?? '' }}</h3>
                <p class="wk-feature-body">{{ $item['body'] ?? '' }}</p>
            </li>
        @endforeach
    </ul>
</section>
