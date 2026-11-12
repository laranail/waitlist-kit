<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions;

use Illuminate\Support\DateFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;
use Simtabi\Laranail\WaitlistKit\Events\SubscriberConverted;

/**
 * Marks a subscriber converted (they used their access) with a convert event. From invited or confirmed only;
 * reporting it again, or for someone who left, changes nothing. Idempotent, so a retrying caller is safe.
 */
final readonly class ConvertSubscriber
{
    public function __construct(private DateFactory $date, private Dispatcher $events) {}

    public function handle(Subscriber $subscriber, ?Actor $actor = null): bool
    {
        if (! in_array($subscriber->status, [SubscriberStatus::Invited, SubscriberStatus::Confirmed], true)) {
            return false;
        }

        $subscriber->getConnection()->transaction(function () use ($subscriber): void {
            $subscriber->forceFill(['status' => SubscriberStatus::Converted])->save();
            $subscriber->events()->create(['campaign_id' => $subscriber->campaign_id, 'type' => FunnelEventType::Convert, 'occurred_at' => $this->date->now()]);
        });

        $this->events->dispatch(new SubscriberConverted($subscriber, $actor ?? Actor::system()));

        return true;
    }
}
