<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions\Admin;

use Illuminate\Contracts\Events\Dispatcher;
use Simtabi\Laranail\WaitlistKit\Models\Block;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Integration;
use Simtabi\Laranail\WaitlistKit\Events\CampaignDeleted;

/**
 * Deletes a campaign for good: its subscribers and funnel events go with it (the foreign keys cascade), and so do its
 * blocks and integrations (polymorphic, so removed here). Enquiries it brought stay, unlinked. Asked for by typing the
 * campaign's slug, under the `erase` ability.
 */
final readonly class DeleteCampaign
{
    public function __construct(private Dispatcher $events) {}

    public function handle(Campaign $campaign, ?Actor $actor = null): void
    {
        [$id, $slug, $name] = [$campaign->id, $campaign->slug, $campaign->name];

        $campaign->getConnection()->transaction(static function () use ($campaign): void {
            $morph = $campaign->getMorphClass();
            Block::query()->where('blockable_type', $morph)->where('blockable_id', $campaign->id)->delete();
            Integration::query()->where('integrable_type', $morph)->where('integrable_id', $campaign->id)->delete();
            $campaign->subscribers()->withTrashed()->forceDelete();
            $campaign->delete();
        });

        $this->events->dispatch(new CampaignDeleted($id, $slug, $name, $actor ?? Actor::system()));
    }
}
