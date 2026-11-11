<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Contracts\Foundation\Application;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** A campaign's page. Only a live campaign has one; any other status is a 404 until later phases give it a page. */
final readonly class CampaignController
{
    public function __construct(private ViewFactory $views, private Application $app, private KitConfig $config) {}

    public function show(Campaign $campaign): View
    {
        if (! $campaign->acceptsSignups()) {
            throw new NotFoundHttpException;
        }

        $locale = in_array($this->app->getLocale(), $campaign->locales, true) ? $this->app->getLocale() : ($campaign->locales[0] ?? 'en');

        return $this->views->make('laranail/waitlist-kit::campaign', [
            'campaign' => $campaign,
            'locale'   => $locale,
            'blocks'   => $campaign->blocks()->where('visible', true)->get(),
            'honeypot' => $this->config->honeypotField(),
            'captcha'  => $this->config->requiresCaptcha(),
        ]);
    }
}
