<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Integrations;

use Simtabi\Laranail\WaitlistKit\Exceptions\UnknownIntegrationDriver;

/**
 * The integration drivers by name. A closed set, written out: an unknown name fails loudly instead of resolving a
 * class from a string stored in the database.
 */
final readonly class Drivers
{
    public function __construct(private AppDriver $app, private BrevoDriver $brevo) {}

    /** @return list<string> */
    public static function names(): array
    {
        return [AppDriver::NAME, BrevoDriver::NAME];
    }

    public function get(string $name): Driver
    {
        return match ($name) {
            AppDriver::NAME   => $this->app,
            BrevoDriver::NAME => $this->brevo,
            default           => throw UnknownIntegrationDriver::named($name, self::names()),
        };
    }
}
