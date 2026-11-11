<section class="wk-block wk-signup" data-block="form" aria-label="{{ __('laranail/waitlist-kit::page.join') }}">
    @include('laranail/waitlist-kit::partials.signup-form', ['label' => $content['label'] ?? null, 'button' => $content['button'] ?? ($campaign->type === \Simtabi\Laranail\WaitlistKit\Enums\CampaignType::ComingSoon ? __('laranail/waitlist-kit::page.notify') : null)])
</section>
