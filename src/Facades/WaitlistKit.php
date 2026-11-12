<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Facades;

use Override;
use Illuminate\Support\Facades\Facade;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\WaitlistKit as Kit;
use Simtabi\Laranail\WaitlistKit\Builders\InviteBuilder;
use Simtabi\Laranail\WaitlistKit\Builders\SignupBuilder;
use Simtabi\Laranail\WaitlistKit\Builders\EnquiryBuilder;
use Simtabi\Laranail\WaitlistKit\Builders\CampaignBuilder;
use Simtabi\Laranail\WaitlistKit\Builders\SubscriberQuery;

/**
 * For application code that prefers a facade to injecting the kit. The kit's own code never uses it.
 *
 * @method static SignupBuilder signup(Campaign|string|null $campaign = null)
 * @method static CampaignBuilder campaign(Campaign|string|null $campaign = null)
 * @method static SubscriberQuery subscribers(Campaign|string $campaign)
 * @method static InviteBuilder invite(Campaign|string $campaign)
 * @method static EnquiryBuilder enquiry()
 * @method static Campaign findCampaign(Campaign|string $campaign)
 *
 * @see Kit
 */
final class WaitlistKit extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return Kit::class;
    }
}
