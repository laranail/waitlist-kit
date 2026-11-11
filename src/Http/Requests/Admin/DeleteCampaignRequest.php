<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Requests\Admin;

use Override;
use Simtabi\Laranail\WaitlistKit\Auth\Abilities;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;

/** Deleting a campaign removes its subscribers for good, so it takes `erase` and the campaign's slug typed back. */
final class DeleteCampaignRequest extends AdminRequest
{
    #[Override]
    protected string $ability = Abilities::ERASE;

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $campaign = $this->route('campaign');

        return ['confirm' => ['required', 'string', 'in:' . ($campaign instanceof Campaign ? $campaign->slug : '')]];
    }
}
