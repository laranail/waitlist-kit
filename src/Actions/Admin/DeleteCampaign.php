<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions\Admin;

use Simtabi\Laranail\WaitlistKit\Models\Block;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Integration;

/**
 * Deletes a campaign for good: its subscribers and funnel events go with it (the foreign keys cascade), and so do its
 * blocks and integrations (polymorphic, so removed here). Enquiries it brought stay, unlinked. Asked for by typing the
 * campaign's slug, under the `erase` ability.
 */
final readonly class DeleteCampaign
{
    public function handle(Campaign $campaign): void
    {
        $campaign->getConnection()->transaction(static function () use ($campaign): void {
            $morph = $campaign->getMorphClass();
            Block::query()->where('blockable_type', $morph)->where('blockable_id', $campaign->id)->delete();
            Integration::query()->where('integrable_type', $morph)->where('integrable_id', $campaign->id)->delete();
            $campaign->subscribers()->withTrashed()->forceDelete();
            $campaign->delete();
        });
    }
}
