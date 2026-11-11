<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Contracts\Foundation\Application;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;
use Simtabi\Laranail\WaitlistKit\Themes\ThemeContextFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * `GET /{prefix}/{campaign}/status/{code}`: a subscriber's own page, reached from their welcome mail. Their place,
 * how many have joined through them, and their referral link with plain share links (no third-party script). The
 * code is the subscriber's unguessable referral code; an unknown code, or someone not on the list, is a 404. The page
 * shows no address, is never indexed and never cached.
 */
final readonly class StatusController
{
    public function __construct(private ViewFactory $views, private Application $app, private ThemeContextFactory $themes) {}

    public function __invoke(Campaign $campaign, string $code): Response
    {
        $subscriber = Subscriber::query()
            ->where('campaign_id', $campaign->id)
            ->where('referral_code', $code)
            ->whereNotNull('position')
            ->where('status', '!=', SubscriberStatus::Unsubscribed)
            ->first() ?? throw new NotFoundHttpException;

        $theme = $this->themes->for($campaign);
        $locale = in_array($this->app->getLocale(), $campaign->locales, true) ? $this->app->getLocale() : ($campaign->locales[0] ?? 'en');
        $link = route('laranail-waitlist-kit.campaign.show', ['campaign' => $campaign->slug, 'ref' => $code]);

        $response = new Response($this->views->make($theme->view('status'), [
            'theme'      => $theme,
            'campaign'   => $campaign,
            'locale'     => $locale,
            'subscriber' => $subscriber,
            'referrals'  => $subscriber->referrals()->whereNotNull('position')->count(),
            'conversion' => $campaign->conversion(),
            'link'       => $link,
            'shares'     => [
                'email'    => 'mailto:?' . http_build_query(['subject' => $campaign->name, 'body' => $link], '', '&', PHP_QUERY_RFC3986),
                'x'        => 'https://x.com/intent/post?' . http_build_query(['url' => $link], '', '&', PHP_QUERY_RFC3986),
                'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?' . http_build_query(['url' => $link], '', '&', PHP_QUERY_RFC3986),
            ],
        ])->render());
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
