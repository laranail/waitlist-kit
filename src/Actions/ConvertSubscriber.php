<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions;

use Illuminate\Support\DateFactory;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;

/**
 * Marks a subscriber converted (they used their access) with a convert event. From invited or confirmed only;
 * reporting it again, or for someone who left, changes nothing. Idempotent, so a retrying caller is safe.
 */
final readonly class ConvertSubscriber
{
    public function __construct(private DateFactory $date) {}

    public function handle(Subscriber $subscriber): bool
    {
        if (! in_array($subscriber->status, [SubscriberStatus::Invited, SubscriberStatus::Confirmed], true)) {
            return false;
        }

        $subscriber->getConnection()->transaction(function () use ($subscriber): void {
            $subscriber->forceFill(['status' => SubscriberStatus::Converted])->save();
            $subscriber->events()->create(['campaign_id' => $subscriber->campaign_id, 'type' => FunnelEventType::Convert, 'occurred_at' => $this->date->now()]);
        });

        return true;
    }
}
