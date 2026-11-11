<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Requests\Admin;

use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Validation\CampaignRules;

final class UpdateCampaignRequest extends AdminRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $campaign = $this->route('campaign');

        return CampaignRules::update($campaign instanceof Campaign ? $campaign->id : '');
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['locales' => LocaleList::from($this->input('locales'))]);
    }
}
