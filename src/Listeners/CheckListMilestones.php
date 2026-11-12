<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Listeners;

use Illuminate\Contracts\Events\Dispatcher;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Illuminate\Contracts\Cache\Repository as Cache;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;
use Simtabi\Laranail\WaitlistKit\Events\SubscriberConfirmed;
use Simtabi\Laranail\WaitlistKit\Events\CampaignMilestoneReached;

/**
 * After someone joins a list, raises CampaignMilestoneReached when the list has grown past one of alerts.milestones.
 *
 * Each milestone fires once per campaign: the cache's atomic add() claims it, so two confirmations at the same moment
 * cannot both announce it. When a list passes several at once (milestones switched on for a list already past them),
 * only the largest is announced and the smaller ones are claimed quietly.
 */
final readonly class CheckListMilestones
{
    public function __construct(
        private KitConfig $config,
        private Cache $cache,
        private Dispatcher $events,
    ) {}

    public function handle(SubscriberConfirmed $event): void
    {
        $milestones = $this->config->milestones();
        if ($milestones === []) {
            return;
        }

        $size = $event->campaign->subscribers()
            ->whereIn('status', [SubscriberStatus::Confirmed, SubscriberStatus::Invited, SubscriberStatus::Converted])
            ->count();

        $newest = null;
        foreach ($milestones as $milestone) {
            if ($size >= $milestone && $this->cache->add('laranail-waitlist-kit.milestone:' . $event->campaign->id . ':' . $milestone, true)) {
                $newest = $milestone;
            }
        }

        if ($newest !== null) {
            $this->events->dispatch(new CampaignMilestoneReached($event->campaign, $newest, Actor::system()));
        }
    }
}
