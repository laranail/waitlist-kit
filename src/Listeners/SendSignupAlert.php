<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Listeners;

use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;
use Simtabi\Laranail\WaitlistKit\Services\AlertDispatcher;
use Simtabi\Laranail\WaitlistKit\Events\SubscriberSignedUp;
use Simtabi\Laranail\WaitlistKit\Notifications\SignupAlert;

/** Alerts the team to a new sign-up, when alerts.on.signup is on. */
final readonly class SendSignupAlert
{
    public function __construct(private KitConfig $config, private AlertDispatcher $alerts) {}

    public function handle(SubscriberSignedUp $event): void
    {
        if (! $this->config->alertsOn('signup')) {
            return;
        }

        $size = $event->campaign->subscribers()->where('status', '!=', SubscriberStatus::Unsubscribed)->count();

        $this->alerts->send(new SignupAlert(
            $event->campaign->name,
            $size,
            $this->alerts->backendUrl('laranail-waitlist-kit.admin.subscribers.index', $event->campaign),
        ));
    }
}
