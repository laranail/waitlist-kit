<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers;

use Illuminate\Contracts\View\View;
use Simtabi\Laranail\WaitlistKit\Models\Page;
use Illuminate\Contracts\Foundation\Application;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Simtabi\Laranail\WaitlistKit\Themes\ThemeContextFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** A campaign's page, in its theme. Only a live campaign has one; any other status is a 404 until Phase 5. */
final readonly class CampaignController
{
    public function __construct(
        private ViewFactory $views,
        private Application $app,
        private KitConfig $config,
        private ThemeContextFactory $themes,
    ) {}

    public function show(Campaign $campaign): View
    {
        if (! $campaign->acceptsSignups()) {
            throw new NotFoundHttpException;
        }

        $theme = $this->themes->for($campaign);
        $locale = in_array($this->app->getLocale(), $campaign->locales, true) ? $this->app->getLocale() : ($campaign->locales[0] ?? 'en');

        return $this->views->make($theme->view('campaign'), [
            'theme'    => $theme,
            'campaign' => $campaign,
            'locale'   => $locale,
            'blocks'   => $campaign->blocks()->where('visible', true)->get(),
            'honeypot' => $this->config->honeypotField(),
            'captcha'  => $this->config->requiresCaptcha(),
            'privacy'  => $this->privacyUrl(),
        ]);
    }

    private function privacyUrl(): ?string
    {
        $privacy = Page::query()->where('slug', Page::PRIVACY)->first();

        return $privacy?->isPublished() === true ? route('laranail-waitlist-kit.page.show', $privacy) : null;
    }
}
