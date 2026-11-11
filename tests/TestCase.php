<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\WaitlistKit\Providers\WaitlistKitServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [WaitlistKitServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $config = $app->make('config');

        // Generated, never committed: the encrypted integration settings need a real key to round-trip.
        $config->set('app.key', 'base64:' . base64_encode(random_bytes(32)));

        $config->set('database.default', 'testing');
        $config->set('database.connections.testing', self::connection());
    }

    /**
     * SQLite in memory, or the MySQL or PostgreSQL service CI's migrations job starts (WAITLIST_KIT_TEST_DRIVER).
     *
     * @return array<string, mixed>
     */
    private static function connection(): array
    {
        $driver = getenv('WAITLIST_KIT_TEST_DRIVER');

        if (! is_string($driver) || $driver === '' || $driver === 'sqlite') {
            return ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''];
        }

        return [
            'driver'   => $driver,
            'host'     => '127.0.0.1',
            'port'     => (string) getenv('WAITLIST_KIT_TEST_PORT'),
            'database' => 'waitlist',
            'username' => $driver === 'pgsql' ? 'postgres' : 'root',
            'password' => (string) getenv('WAITLIST_KIT_TEST_PASSWORD'),
            'charset'  => $driver === 'pgsql' ? 'utf8' : 'utf8mb4',
            'prefix'   => '',
        ];
    }
}
