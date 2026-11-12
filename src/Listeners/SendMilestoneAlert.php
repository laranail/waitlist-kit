<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Listeners;

use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Services\AlertDispatcher;
use Simtabi\Laranail\WaitlistKit\Notifications\MilestoneAlert;
use Simtabi\Laranail\WaitlistKit\Events\CampaignMilestoneReached;

/** Alerts the team when a campaign's list reaches a milestone, when alerts.on.milestone is on. */
final readonly class SendMilestoneAlert
{
    public function __construct(private KitConfig $config, private AlertDispatcher $alerts) {}

    public function handle(CampaignMilestoneReached $event): void
    {
        if (! $this->config->alertsOn('milestone')) {
            return;
        }

        $this->alerts->send(new MilestoneAlert(
            $event->campaign->name,
            $event->size,
            $this->alerts->backendUrl('laranail-waitlist-kit.admin.campaigns.funnel', $event->campaign),
        ));
    }
}
