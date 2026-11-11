<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Validation;

use Illuminate\Validation\Rule;
use Simtabi\Laranail\WaitlistKit\Support\Tables;
use Simtabi\Laranail\WaitlistKit\Integrations\Drivers;
use Simtabi\Laranail\WaitlistKit\Integrations\AppDriver;
use Simtabi\Laranail\WaitlistKit\Integrations\BrevoDriver;

/**
 * An integration's fields, per driver. A secret already stored may be left empty on edit, which keeps it, so the form
 * never has to show it back.
 */
final class IntegrationRules
{
    /** @return array<string, list<mixed>> */
    public static function rules(string $driver, bool $editing): array
    {
        $rules = [
            'driver'   => ['required', Rule::in(Drivers::names())],
            'campaign' => ['nullable', 'string', Rule::exists(Tables::name('campaigns'), 'slug')],
            'enabled'  => ['sometimes', 'boolean'],
        ];

        return $rules + match ($driver) {
            AppDriver::NAME   => ['endpoint' => ['required', 'string', 'max:2000', 'url:https', 'starts_with:https://']],
            BrevoDriver::NAME => [
                'api_key' => [$editing ? 'nullable' : 'required', 'string', 'max:200'],
                'list_id' => ['required', 'integer', 'min:1'],
            ],
            default => [],
        };
    }
}
