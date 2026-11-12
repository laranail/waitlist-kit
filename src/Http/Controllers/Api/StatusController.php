<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * `GET /{prefix}/{api}/campaigns/{slug}/status/{code}`: a subscriber's place, as the status page shows it, keyed by
 * their unguessable referral code. No address in the answer; an unknown code is a 404; never cached.
 */
final readonly class StatusController
{
    public function __invoke(Campaign $campaign, string $code): JsonResponse
    {
        $subscriber = Subscriber::query()
            ->where('campaign_id', $campaign->id)
            ->where('referral_code', $code)
            ->whereNotNull('position')
            ->where('status', '!=', SubscriberStatus::Unsubscribed)
            ->first() ?? throw new NotFoundHttpException;

        $response = new JsonResponse(['data' => [
            'status'       => $subscriber->status->value,
            'position'     => $subscriber->position,
            'referrals'    => $subscriber->referrals()->whereNotNull('position')->count(),
            'referral_url' => route('laranail-waitlist-kit.campaign.show', ['campaign' => $campaign->slug, 'ref' => $code]),
        ]]);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
