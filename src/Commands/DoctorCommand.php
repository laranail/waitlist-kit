<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Commands;

use Override;
use Throwable;
use RuntimeException;
use Illuminate\Database\Connection;
use Illuminate\Contracts\Foundation\Application;
use Simtabi\Laranail\WaitlistKit\Support\Tables;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Illuminate\Database\ConnectionResolverInterface;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\WaitlistKit\Models\Integration;
use Simtabi\Laranail\WaitlistKit\Themes\ThemeAssets;
use Simtabi\Laranail\WaitlistKit\Integrations\Drivers;
use Simtabi\Laranail\WaitlistKit\Themes\ThemeRegistry;
use Simtabi\Laranail\Captcha\Providers\CaptchaServiceProvider;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;

/**
 * Checks the install: the configuration is valid, the database answers, and every table the kit needs exists.
 * Exits non-zero on any problem, so a deploy can run it as a gate.
 */
final class DoctorCommand extends Command
{
    use SupportsNamespacedNames;

    /** The kit's tables, without the configured prefix. */
    public const array TABLES = ['campaigns', 'subscribers', 'enquiries', 'pages', 'blocks', 'events', 'integrations'];

    /** The provider whose `laranail_captcha` rule the sign-up form needs when security.captcha is on. */
    public const string CAPTCHA_PROVIDER = CaptchaServiceProvider::class;

    #[Override]
    protected $name = 'laranail::waitlist-kit.doctor';

    #[Override]
    protected $description = 'Check the waitlist-kit configuration, database connection and tables.';

    public function handle(KitConfig $config, ConnectionResolverInterface $connections, Application $app, ThemeRegistry $themes, ThemeAssets $assets): int
    {
        $this->services->display()->header('laranail/waitlist-kit');

        $problems = $config->problems();

        if (! $themes->has($config->defaultTheme())) {
            $problems[] = sprintf('themes.default names "%s", which is not installed; installed: %s.', $config->defaultTheme(), implode(', ', array_keys($themes->all())));
        }

        foreach ($themes->all() as $theme) {
            if (! $assets->published($theme)) {
                $problems[] = sprintf('The %s theme\'s assets are not published, so its pages render unstyled. Run php artisan vendor:publish --tag=laranail::waitlist-kit-assets --force.', $theme->name);
            }
        }

        if ($config->requiresCaptcha() && ! $app->providerIsLoaded(self::CAPTCHA_PROVIDER)) {
            $problems[] = 'security.captcha is on, but laranail/captcha is not installed: every sign-up would fail validation.';
        }

        try {
            $connection = $connections->connection($config->connection());
            if (! $connection instanceof Connection) {
                throw new RuntimeException('The connection is not an Illuminate database connection.');
            }
            $schema = $connection->getSchemaBuilder();
            $missing = array_values(array_filter(self::TABLES, static fn (string $table): bool => ! $schema->hasTable(Tables::name($table))));
            if ($missing !== []) {
                $problems[] = sprintf('Missing tables: %s. Run php artisan migrate.', implode(', ', array_map(Tables::name(...), $missing)));
            } else {
                $orphans = Campaign::query()->whereNotNull('theme')->whereNotIn('theme', array_keys($themes->all()))->pluck('slug')->all();
                if ($orphans !== []) {
                    $problems[] = sprintf('Campaigns name a theme that is not installed (they fall back to the default): %s.', implode(', ', array_map(static fn (mixed $s): string => is_scalar($s) ? (string) $s : '', $orphans)));
                }

                $unknown = Integration::query()->whereNotIn('driver', Drivers::names())->distinct()->pluck('driver')
                    ->map(static fn (mixed $driver): string => is_scalar($driver) ? (string) $driver : '')->all();
                if ($unknown !== []) {
                    $problems[] = sprintf('Integrations name unknown drivers: %s. The kit has: %s.', implode(', ', $unknown), implode(', ', Drivers::names()));
                }
            }
        } catch (Throwable $e) {
            $problems[] = 'The database did not answer: ' . $e->getMessage();
        }

        $this->services->display()->keyValue([
            'Campaigns' => $this->describe(static fn (): string => $config->campaignsMode()->value),
            'Sign-in'   => $this->describe(static fn (): string => $config->authMode()->value),
            'Theme'     => $config->defaultTheme(),
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
