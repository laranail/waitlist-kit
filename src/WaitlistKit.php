<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit;

use Illuminate\Contracts\Container\Container;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Builders\InviteBuilder;
use Simtabi\Laranail\WaitlistKit\Builders\SignupBuilder;
use Simtabi\Laranail\WaitlistKit\Support\CampaignLookup;
use Simtabi\Laranail\WaitlistKit\Builders\EnquiryBuilder;
use Simtabi\Laranail\WaitlistKit\Builders\CampaignBuilder;
use Simtabi\Laranail\WaitlistKit\Builders\SubscriberQuery;

/**
 * The kit from application code: one entry point, each method a fresh fluent builder whose terminal call runs an
 * action. Inject it (or use the WaitlistKit facade):
 *
 *     $kit->signup('launch')->email('dana@example.com')->subscribe();
 *     $kit->campaign()->named('Launch')->slug('launch')->live()->create();
 *     $kit->subscribers('launch')->confirmed()->byPosition()->paginate();
 *     $kit->invite('launch')->top(25)->as($user)->send();
 *     $kit->enquiry()->from('Dana', 'dana@example.com')->topic('sales')->message('...')->submit();
 *
 * Builders act as the system unless told `->as($user)`, which checks that user against the Gate and policies.
 */
final readonly class WaitlistKit
{
    public function __construct(private Container $container) {}

    public function signup(Campaign|string|null $campaign = null): SignupBuilder
    {
        $builder = $this->container->make(SignupBuilder::class);

        return $campaign === null ? $builder : $builder->to($campaign);
    }

    /** A new campaign, or (given one) an existing campaign to change or delete. */
    public function campaign(Campaign|string|null $campaign = null): CampaignBuilder
    {
        $builder = $this->container->make(CampaignBuilder::class);

        return $campaign === null ? $builder : $builder->of($campaign);
    }

    public function subscribers(Campaign|string $campaign): SubscriberQuery
    {
        return $this->container->make(SubscriberQuery::class, ['campaign' => CampaignLookup::resolve($campaign)]);
    }

    public function invite(Campaign|string $campaign): InviteBuilder
    {
        return $this->container->make(InviteBuilder::class, ['campaign' => CampaignLookup::resolve($campaign)]);
    }

    public function enquiry(): EnquiryBuilder
    {
        return $this->container->make(EnquiryBuilder::class);
    }

    /** A campaign by model, id or slug. */
    public function findCampaign(Campaign|string $campaign): Campaign
    {
        return CampaignLookup::resolve($campaign);
    }
}
