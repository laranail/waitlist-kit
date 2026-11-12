<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers\Api;

use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignStatus;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Simtabi\Laranail\WaitlistKit\Http\Resources\PublicCampaignResource;

/** `GET /{prefix}/{api}/campaigns/{slug}`: a published campaign, for a headless front end. A draft is a 404. */
final readonly class CampaignController
{
    public function show(Campaign $campaign): PublicCampaignResource
    {
        if ($campaign->status === CampaignStatus::Draft) {
            throw new NotFoundHttpException;
        }

        return new PublicCampaignResource($campaign);
    }
}
