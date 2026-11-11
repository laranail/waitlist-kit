<footer class="wk-block wk-footer" data-block="footer">
    @if (($note = $content['text'] ?? $theme->text('footer')) !== '')<p class="wk-footer-note">{{ $note }}</p>@endif
    <x-laranail-waitlist-kit::legal-links class="wk-legal-links" />
    @if ($theme->enabled('show-powered-by'))<p class="wk-powered-by">Powered by laranail/waitlist-kit</p>@endif
</footer>
