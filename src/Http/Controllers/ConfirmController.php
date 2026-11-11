<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Actions\ConfirmSubscriber;

/**
 * `GET /{prefix}/{campaign}/confirm/{subscriber}`, signed and expiring: confirms the address and returns to the
 * campaign's page, which says so. A tampered or expired link answers 403 before it reaches here.
 */
final readonly class ConfirmController
{
    public const string FLASH = 'laranail/waitlist-kit.confirmed';

    public function __invoke(Campaign $campaign, Subscriber $subscriber, ConfirmSubscriber $confirm): RedirectResponse
    {
        $confirm->handle($campaign, $subscriber);

        $response = redirect()->route('laranail-waitlist-kit.campaign.show', $campaign)->with(self::FLASH, $subscriber->position);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
