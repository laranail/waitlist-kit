<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Validation;

use Illuminate\Validation\Rule;
use Simtabi\Laranail\WaitlistKit\Support\Tables;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;

/**
 * The rules a campaign's own fields must meet, in one place: CreateCampaign applies them, and the backend's requests
 * will reuse them, so no path can store a campaign the others would refuse.
 */
final class CampaignRules
{
    public const string SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** @return array<string, list<mixed>> */
    public static function create(): array
    {
        return [
            'name'      => ['required', 'string', 'max:120'],
            'slug'      => ['required', 'string', 'max:120', 'regex:' . self::SLUG_PATTERN, Rule::unique(Tables::name('campaigns'), 'slug')],
            'type'      => ['required', Rule::enum(CampaignType::class)],
            'locales'   => ['required', 'array', 'min:1', 'max:20'],
            'locales.*' => ['required', 'distinct', new Locale],
        ];
    }
}
