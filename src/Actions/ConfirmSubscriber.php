<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions;

use Illuminate\Support\DateFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;
use Simtabi\Laranail\WaitlistKit\Events\SubscriberConfirmed;

/**
 * Confirms a pending subscriber from their signed link: they join the list (AdmitToList), the funnel records the
 * confirmation, and the welcome (thank-you mail, integrations) follows the commit. Only a pending subscriber changes;
 * opening the link again, or after leaving the list, changes nothing and is not an error.
 */
final readonly class ConfirmSubscriber
{
    public function __construct(
        private AdmitToList $admit,
        private WelcomeSubscriber $welcome,
        private DateFactory $date,
        private Dispatcher $events,
    ) {}

    /** Whether this call confirmed them (false when they already were, or have left). */
    public function handle(Campaign $campaign, Subscriber $subscriber, ?Actor $actor = null): bool
    {
        if ($subscriber->status !== SubscriberStatus::Pending) {
            return false;
        }

        $subscriber->getConnection()->transaction(function () use ($campaign, $subscriber): void {
            $this->admit->handle($subscriber);
            $subscriber->events()->create(['campaign_id' => $campaign->id, 'type' => FunnelEventType::Confirm, 'occurred_at' => $this->date->now()]);
        });

        $this->welcome->handle($campaign, $subscriber);
        $this->events->dispatch(new SubscriberConfirmed($campaign, $subscriber, $actor ?? Actor::system()));

        return true;
    }
}
