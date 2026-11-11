<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Database\Factories;

use Override;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Models\FunnelEvent;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;

/** @extends Factory<FunnelEvent> */
final class FunnelEventFactory extends Factory
{
    #[Override]
    protected $model = FunnelEvent::class;

    /** A page view of a new campaign; use about() for a step about a subscriber. */
    public function definition(): array
    {
        $campaign = Campaign::factory();

        return [
            'campaign_id'    => $campaign,
            'eventable_type' => (new Campaign)->getMorphClass(),
            'eventable_id'   => static fn (array $attributes): mixed => $attributes['campaign_id'],
            'type'           => FunnelEventType::View,
            'occurred_at'    => now(),
        ];
    }

    public function about(Subscriber $subscriber, FunnelEventType $type = FunnelEventType::Signup): self
    {
        return $this->state([
            'campaign_id'    => $subscriber->campaign_id,
            'eventable_type' => $subscriber->getMorphClass(),
            'eventable_id'   => $subscriber->id,
            'type'           => $type,
        ]);
    }
}
