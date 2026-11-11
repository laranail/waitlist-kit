<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Requests\Admin;

use Simtabi\Laranail\WaitlistKit\Validation\CampaignRules;

final class StoreCampaignRequest extends AdminRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return CampaignRules::create();
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['locales' => LocaleList::from($this->input('locales'))]);
    }
}
