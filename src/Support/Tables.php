<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Support;

/**
 * The kit's table names and connection, set once by the service provider from configuration.
 *
 * Static because Eloquent asks a model for its table without a container in reach. It holds configuration, never
 * anything derived from a request, so it is the same for every request a long-running worker serves.
 */
final class Tables
{
    private static string $prefix = 'laranail_waitlist_kit_';

    private static ?string $connection = null;

    public static function configure(string $prefix, ?string $connection): void
    {
        self::$prefix = $prefix;
        self::$connection = $connection;
    }

    public static function name(string $table): string
    {
        return self::$prefix . $table;
    }

    public static function connection(): ?string
    {
        return self::$connection;
    }
}
