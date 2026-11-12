<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Resources;

use Override;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;

/**
 * A subscriber as a campaign's managers see it. Only the `manage/*` endpoints return it: no public answer carries an
 * address.
 *
 * @property Subscriber $resource
 */
final class SubscriberResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $subscriber = $this->resource;

        return [
            'id'             => $subscriber->id,
            'campaign_id'    => $subscriber->campaign_id,
            'email'          => $subscriber->email,
            'status'         => $subscriber->status->value,
            'locale'         => $subscriber->locale,
            'position'       => $subscriber->position,
            'referral_code'  => $subscriber->referral_code,
            'referred_by_id' => $subscriber->referred_by_id,
            'attribution'    => $subscriber->attribution,
            'created_at'     => Dates::iso($subscriber->getAttribute('created_at')),
        ];
    }
}
