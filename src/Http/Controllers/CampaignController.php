<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\DateFactory;
use Illuminate\Http\RedirectResponse;
use Simtabi\Laranail\WaitlistKit\Models\Page;
use Illuminate\Contracts\Foundation\Application;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;
use Simtabi\Laranail\WaitlistKit\Models\FunnelEvent;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignStatus;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;
use Simtabi\Laranail\WaitlistKit\Themes\ThemeContextFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A campaign's public page, in its theme and language, by status: a draft has none (404), a live campaign shows its
 * page and takes sign-ups, a closed one says so, and a launched one sends visitors to its launch address or says it
 * has launched. Each live page view is a funnel event (the campaign itself as the subject; no visitor data), except
 * a browser's prefetch. `?lang=` picks one of the campaign's languages.
 */
final readonly class CampaignController
{
    public function __construct(
        private ViewFactory $views,
        private Application $app,
        private KitConfig $config,
        private ThemeContextFactory $themes,
        private DateFactory $date,
    ) {}

    public function show(Request $request, Campaign $campaign): View|RedirectResponse
    {
        if ($campaign->status === CampaignStatus::Draft) {
            throw new NotFoundHttpException;
        }
        if ($campaign->status === CampaignStatus::Launched && ($launch = $campaign->launchUrl()) !== null) {
            return redirect()->away($launch);
        }

        $lang = $request->query('lang');
        $locale = $campaign->localeFor(is_string($lang) ? $lang : $this->app->getLocale());
        $this->app->setLocale($locale);
        $theme = $this->themes->for($campaign);
        $meta = $this->meta($campaign, $locale);

        if ($campaign->status !== CampaignStatus::Live) {
            return $this->views->make($theme->view('notice'), [
                'theme'    => $theme,
                'campaign' => $campaign,
                'locale'   => $locale,
                'meta'     => $meta,
                'kind'     => $campaign->status === CampaignStatus::Closed ? 'closed' : 'launched',
            ]);
        }

        if (! in_array('prefetch', [$request->header('Sec-Purpose'), $request->header('Purpose')], true)) {
            (new FunnelEvent)->forceFill([
                'campaign_id'    => $campaign->id,
                'eventable_type' => $campaign->getMorphClass(),
                'eventable_id'   => $campaign->id,
                'type'           => FunnelEventType::View,
                'occurred_at'    => $this->date->now(),
            ])->save();
        }

        return $this->views->make($theme->view('campaign'), [
            'theme'    => $theme,
            'campaign' => $campaign,
            'locale'   => $locale,
            'meta'     => $meta,
            'blocks'   => $campaign->blocks()->where('visible', true)->get(),
            'honeypot' => $this->config->honeypotField(),
            'captcha'  => $this->config->requiresCaptcha(),
            'privacy'  => $this->privacyUrl(),
            'access'   => $campaign->type === CampaignType::EarlyAccess && $campaign->conversion()->inviteUrl !== null,
        ]);
    }

    /**
     * The page's head: title, description, canonical address, share image, robots, and the other languages.
     *
     * @return array{title: string, description: ?string, canonical: string, image: ?string, robots: string, alternates: array<string, string>}
     */
    private function meta(Campaign $campaign, string $locale): array
    {
        $seo = $campaign->seo();
        $fallback = $campaign->locales[0] ?? 'en';
        $url = fn (string $code): string => route('laranail-waitlist-kit.campaign.show', $code === $fallback
            ? ['campaign' => $campaign->slug]
            : ['campaign' => $campaign->slug, 'lang' => $code]);

        $alternates = [];
        if ($campaign->indexable() && count($campaign->locales) > 1) {
            foreach ($campaign->locales as $code) {
                $alternates[$code] = $url($code);
            }
            $alternates['x-default'] = $url($fallback);
        }

        return [
            'title'       => $seo->title($locale, $fallback, $campaign->name),
            'description' => $seo->description($locale, $fallback),
            'canonical'   => $url($locale),
            'image'       => $seo->image,
            'robots'      => $campaign->indexable() ? 'index, follow' : 'noindex',
            'alternates'  => $alternates,
        ];
    }

    private function privacyUrl(): ?string
    {
        $privacy = Page::query()->where('slug', Page::PRIVACY)->first();

        return $privacy?->isPublished() === true ? route('laranail-waitlist-kit.page.show', $privacy) : null;
    }
}
