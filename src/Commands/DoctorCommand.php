<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Commands;

use Override;
use Throwable;
use RuntimeException;
use Illuminate\Database\Connection;
use Simtabi\Laranail\WaitlistKit\Support\Tables;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Illuminate\Database\ConnectionResolverInterface;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;

/**
 * Checks the install: the configuration is valid, the database answers, and every table the kit needs exists.
 * Exits non-zero on any problem, so a deploy can run it as a gate.
 */
final class DoctorCommand extends Command
{
    use SupportsNamespacedNames;

    /** The kit's tables, without the configured prefix. */
    public const array TABLES = ['campaigns', 'subscribers', 'blocks', 'events', 'integrations'];

    #[Override]
    protected $name = 'laranail::waitlist-kit.doctor';

    #[Override]
    protected $description = 'Check the waitlist-kit configuration, database connection and tables.';

    public function handle(KitConfig $config, ConnectionResolverInterface $connections): int
    {
        $this->services->display()->header('laranail/waitlist-kit');

        $problems = $config->problems();

        try {
            $connection = $connections->connection($config->connection());
            if (! $connection instanceof Connection) {
                throw new RuntimeException('The connection is not an Illuminate database connection.');
            }
            $schema = $connection->getSchemaBuilder();
            $missing = array_values(array_filter(self::TABLES, static fn (string $table): bool => ! $schema->hasTable(Tables::name($table))));
            if ($missing !== []) {
                $problems[] = sprintf('Missing tables: %s. Run php artisan migrate.', implode(', ', array_map(Tables::name(...), $missing)));
            }
        } catch (Throwable $e) {
            $problems[] = 'The database did not answer: ' . $e->getMessage();
        }

        $this->services->display()->keyValue([
            'Campaigns' => $this->describe(static fn (): string => $config->campaignsMode()->value),
            'Sign-in'   => $this->describe(static fn (): string => $config->authMode()->value),
            'Tables'    => Tables::name('*'),
        ]);

        foreach ($problems as $problem) {
            $this->services->display()->error($problem);
        }

        if ($problems !== []) {
            return self::FAILURE;
        }

        $this->services->display()->success('Ready.');

        return self::SUCCESS;
    }

    /** @param  callable(): string  $read */
    private function describe(callable $read): string
    {
        try {
            return $read();
        } catch (Throwable) {
            return '(invalid)';
        }
    }
}
