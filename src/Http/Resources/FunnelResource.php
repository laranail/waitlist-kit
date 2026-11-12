<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Resources;

use Override;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Simtabi\Laranail\WaitlistKit\Data\FunnelSummary;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;

/**
 * A campaign's funnel: counts per step, the rates between them, sign-ups per day, and the top sources.
 *
 * @property FunnelSummary $resource
 */
final class FunnelResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $funnel = $this->resource;

        return [
            'days'   => $funnel->days,
            'counts' => $funnel->counts,
            'rates'  => [
                'confirm' => $funnel->rate(FunnelEventType::Signup, FunnelEventType::Confirm),
                'invite'  => $funnel->rate(FunnelEventType::Confirm, FunnelEventType::Invite),
                'convert' => $funnel->rate(FunnelEventType::Invite, FunnelEventType::Convert),
            ],
            'signups_by_day' => $funnel->byDay,
            'sources'        => $funnel->sources,
        ];
    }
}
