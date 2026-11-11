<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Providers;

use Override;
use Illuminate\Contracts\Config\Repository;
use Simtabi\Laranail\Package\Tools\Package;
use Illuminate\Contracts\Foundation\Application;
use Simtabi\Laranail\WaitlistKit\Support\Tables;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Commands\DoctorCommand;
use Simtabi\Laranail\WaitlistKit\Commands\InstallCommand;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;

/**
 * Registers the kit: config at `laranail.waitlist-kit`, the migrations (run by `php artisan migrate`, or published
 * with `--tag=laranail::waitlist-kit-migrations`), and the `laranail::waitlist-kit.*` commands.
 *
 * Nothing here touches the database or the network, so an outage cannot fail the application's boot.
 */
final class WaitlistKitServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laranail/waitlist-kit')
            ->setPublishTagId('waitlist-kit')
            ->hasConfigFile('waitlist-kit')
            ->hasCommands([DoctorCommand::class, InstallCommand::class])
            ->discoversMigrations()
            ->runsMigrations();
    }

    #[Override]
    public function packageRegistered(): void
    {
        // The install command's package-tools base takes the Package in its constructor, which the container
        // cannot autowire.
        $this->app->bind(InstallCommand::class, fn (): InstallCommand => new InstallCommand($this->package));

        $this->app->bind(KitConfig::class, static fn (Application $app): KitConfig => new KitConfig($app->make(Repository::class)));
    }

    #[Override]
    public function packageBooted(): void
    {
        // Configuration, not request state: the same for every request a long-running worker serves.
        $config = $this->app->make(KitConfig::class);
        Tables::configure($config->tablePrefix(), $config->connection());
    }
}
