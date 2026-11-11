<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Validation;

use Illuminate\Validation\Rule;
use Simtabi\Laranail\WaitlistKit\Support\Tables;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignStatus;

/**
 * What an enquiry must carry, from the configuration: the topics on offer, whether the company is asked, and whether
 * agreeing to the privacy notice is required. Every field is bounded.
 */
final class ContactRules
{
    /** @return array<string, list<mixed>> */
    public static function rules(KitConfig $config): array
    {
        $rules = [
            'name'     => ['required', 'string', 'min:2', 'max:120'],
            'email'    => ['required', 'string', 'max:254', 'email:rfc'],
            'topic'    => ['required', 'string', Rule::in($config->contactTopics())],
            'message'  => ['required', 'string', 'min:10', 'max:2000'],
            'locale'   => ['nullable', 'string', new Locale],
            'campaign' => ['nullable', 'string', Rule::exists(Tables::name('campaigns'), 'slug')->where('status', CampaignStatus::Live->value)],
            'return'   => ['nullable', 'string', 'max:2000'],
        ];

        $company = $config->contactCompany();
        if ($company !== 'off') {
            $rules['company'] = [$company === 'required' ? 'required' : 'nullable', 'string', 'max:160'];
        }

        if ($config->contactRequiresConsent()) {
            $rules['agree'] = ['accepted'];
        }

        return $rules;
    }
}
