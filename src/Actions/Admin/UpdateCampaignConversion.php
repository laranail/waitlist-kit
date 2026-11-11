<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions\Admin;

use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Data\ConversionSettings;

/** Saves a campaign's conversion settings into `settings.conversion`, leaving its other settings as they were. */
final readonly class UpdateCampaignConversion
{
    public function handle(Campaign $campaign, ConversionSettings $conversion): void
    {
        $campaign->update(['settings' => [...$campaign->settings, 'conversion' => $conversion->toArray()]]);
    }
}
