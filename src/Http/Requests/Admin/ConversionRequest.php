<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Requests\Admin;

use Simtabi\Laranail\WaitlistKit\Data\ConversionSettings;

/** A campaign's conversion settings, as the campaign screen's Conversion card posts them. Needs `manage`. */
final class ConversionRequest extends AdminRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'double_opt_in' => ['required', 'boolean'],
            'referrals'     => ['required', 'boolean'],
            'referral_bump' => ['required', 'integer', 'min:0', 'max:' . ConversionSettings::MAX_BUMP],
            'referral_cap'  => ['required', 'integer', 'min:0', 'max:' . ConversionSettings::MAX_CAP],
            'invite_url'    => ['nullable', 'string', 'max:2000', 'url:https'],
        ];
    }

    public function toData(): ConversionSettings
    {
        return new ConversionSettings(
            doubleOptIn: $this->boolean('double_opt_in'),
            referrals: $this->boolean('referrals'),
            referralBump: $this->integer('referral_bump'),
            referralCap: $this->integer('referral_cap'),
            inviteUrl: $this->filled('invite_url') ? $this->string('invite_url')->toString() : null,
        );
    }
}
