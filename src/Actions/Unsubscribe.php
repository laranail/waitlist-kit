<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions;

use Illuminate\Support\DateFactory;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;

/**
 * Takes a subscriber off the list at their request: status unsubscribed and an unsubscribe event. They keep their row
 * (so a later sign-up is a repeat, not a new place) and drop out of invites and referral credit. Idempotent.
 */
final readonly class Unsubscribe
{
    public function __construct(private DateFactory $date) {}

    /** Whether this call took them off (false when they had already left). */
    public function handle(Subscriber $subscriber): bool
    {
        if ($subscriber->status === SubscriberStatus::Unsubscribed) {
            return false;
        }

        $subscriber->getConnection()->transaction(function () use ($subscriber): void {
            $subscriber->forceFill(['status' => SubscriberStatus::Unsubscribed])->save();
            $subscriber->events()->create(['campaign_id' => $subscriber->campaign_id, 'type' => FunnelEventType::Unsubscribe, 'occurred_at' => $this->date->now()]);
        });

        return true;
    }
}
