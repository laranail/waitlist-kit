<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Providers;

use Override;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\RateLimiter;
use Simtabi\Laranail\Package\Tools\Package;
use Illuminate\Contracts\Foundation\Application;
use Simtabi\Laranail\WaitlistKit\Support\Tables;
use Simtabi\Laranail\WaitlistKit\Support\MorphMap;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Illuminate\Database\Eloquent\Relations\Relation;
use Simtabi\Laranail\WaitlistKit\Commands\DoctorCommand;
use Simtabi\Laranail\WaitlistKit\Commands\ExportCommand;
use Simtabi\Laranail\WaitlistKit\Commands\ForgetCommand;
use Simtabi\Laranail\WaitlistKit\Commands\InstallCommand;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;
use Simtabi\Laranail\WaitlistKit\Database\Seeders\WaitlistKitSeeder;

/**
 * Registers the kit: config at `laranail.waitlist-kit`, the migrations (run by `php artisan migrate`, or published
 * with `--tag=laranail::waitlist-kit-migrations`), and the `laranail::waitlist-kit.*` commands.
 *
 * Nothing here touches the database or the network, so an outage cannot fail the application's boot.
 */
final class WaitlistKitServiceProvider extends PackageServiceProvider
{
    public const string SIGNUP_LIMITER = 'laranail-waitlist-kit.signups';

    public const string ENQUIRY_LIMITER = 'laranail-waitlist-kit.enquiries';

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laranail/waitlist-kit')
            ->setPublishTagId('waitlist-kit')
            ->hasConfigFile('waitlist-kit')
            ->hasViews()
            ->hasBladeComponentNamespace('Simtabi\\Laranail\\WaitlistKit\\View\\Components', 'laranail-waitlist-kit')
            ->hasTranslations()
            ->hasRoutesWhen('laranail.waitlist-kit.routes.enabled', 'web', true)
            ->hasCommands([DoctorCommand::class, ExportCommand::class, ForgetCommand::class, InstallCommand::class])
            ->discoversMigrations()
            ->runsMigrations()
            ->registerSeeder(WaitlistKitSeeder::class);
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

        // Merged, never replacing the application's own map, and never enforced globally (see MorphMap).
        Relation::morphMap(MorphMap::MAP);

        // Sign-ups per campaign per visitor. `$request->ip()` honours the application's TrustProxies, so behind
        // Cloudflare or a load balancer each visitor has their own limit once the proxy is trusted there.
        RateLimiter::for(self::SIGNUP_LIMITER, static function (Request $request) use ($config): Limit {
            // The raw slug: Laravel's middleware priority runs throttling before route-model binding.
            $slug = $request->route()?->originalParameter('campaign');

            return Limit::perMinute($config->signupsPerMinute())
                ->by((is_string($slug) ? $slug : '') . '|' . $request->ip());
        });

        RateLimiter::for(self::ENQUIRY_LIMITER, static fn (Request $request): Limit => Limit::perMinute($config->enquiriesPerMinute())->by((string) $request->ip()));
    }
}
