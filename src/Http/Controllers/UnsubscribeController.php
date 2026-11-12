<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Illuminate\Contracts\Foundation\Application;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Simtabi\Laranail\WaitlistKit\Actions\Unsubscribe;
use Simtabi\Laranail\WaitlistKit\Themes\ThemeContextFactory;

/**
 * The signed unsubscribe link every mail carries. GET shows a page with one button, so a mail scanner that opens links
 * cannot take anyone off the list; POST (the button, or a mail client's one-click unsubscribe, RFC 8058) does it.
 */
final readonly class UnsubscribeController
{
    public function __construct(private ViewFactory $views, private Application $app, private ThemeContextFactory $themes) {}

    public function show(Request $request, Campaign $campaign, Subscriber $subscriber): Response
    {
        return $this->page($campaign, $subscriber, done: false, action: $request->fullUrl());
    }

    public function store(Request $request, Campaign $campaign, Subscriber $subscriber, Unsubscribe $unsubscribe): Response
    {
        $unsubscribe->handle($subscriber, Actor::from($request->user()));

        return $this->page($campaign, $subscriber, done: true, action: $request->fullUrl());
    }

    private function page(Campaign $campaign, Subscriber $subscriber, bool $done, string $action): Response
    {
        $theme = $this->themes->for($campaign);
        $locale = in_array($subscriber->locale, $campaign->locales, true) ? $subscriber->locale : ($campaign->locales[0] ?? 'en');
        $this->app->setLocale($locale);

        $response = new Response($this->views->make($theme->view('unsubscribe'), [
            'theme'    => $theme,
            'campaign' => $campaign,
            'locale'   => $locale,
            'done'     => $done,
            'action'   => $action,
        ])->render());
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
