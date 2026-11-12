<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Resources;

use Override;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Simtabi\Laranail\WaitlistKit\Models\Enquiry;

/**
 * A contact enquiry, for the people who answer them.
 *
 * @property Enquiry $resource
 */
final class EnquiryResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $enquiry = $this->resource;

        return [
            'id'          => $enquiry->id,
            'campaign_id' => $enquiry->campaign_id,
            'name'        => $enquiry->name,
            'email'       => $enquiry->email,
            'company'     => $enquiry->company,
            'topic'       => $enquiry->topic,
            'message'     => $enquiry->message,
            'locale'      => $enquiry->locale,
            'consent'     => $enquiry->consent,
            'created_at'  => Dates::iso($enquiry->getAttribute('created_at')),
        ];
    }
}
