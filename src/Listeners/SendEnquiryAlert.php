<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Listeners;

use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Events\EnquiryReceived;
use Simtabi\Laranail\WaitlistKit\Services\AlertDispatcher;
use Simtabi\Laranail\WaitlistKit\Notifications\EnquiryAlert;

/** Alerts the team to a contact enquiry, when alerts.on.enquiry is on. */
final readonly class SendEnquiryAlert
{
    public function __construct(private KitConfig $config, private AlertDispatcher $alerts) {}

    public function handle(EnquiryReceived $event): void
    {
        if (! $this->config->alertsOn('enquiry')) {
            return;
        }

        $this->alerts->send(new EnquiryAlert(
            $event->enquiry->topic,
            $this->alerts->backendUrl('laranail-waitlist-kit.admin.enquiries.show', $event->enquiry),
        ));
    }
}
