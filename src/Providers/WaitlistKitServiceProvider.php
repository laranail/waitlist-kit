<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Providers;

use Override;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\RateLimiter;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\WaitlistKit\Models\Admin;
use Illuminate\Contracts\Foundation\Application;
use Simtabi\Laranail\WaitlistKit\Auth\Abilities;
use Simtabi\Laranail\WaitlistKit\Support\Tables;
use Simtabi\Laranail\WaitlistKit\Support\MorphMap;
use Simtabi\Laranail\WaitlistKit\Themes\KitThemes;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Illuminate\Database\Eloquent\Relations\Relation;
use Simtabi\Laranail\WaitlistKit\Themes\ThemeRegistry;
use Simtabi\Laranail\WaitlistKit\Commands\AdminCommand;
use Simtabi\Laranail\WaitlistKit\Commands\DoctorCommand;
use Simtabi\Laranail\WaitlistKit\Commands\ExportCommand;
use Simtabi\Laranail\WaitlistKit\Commands\ForgetCommand;
use Simtabi\Laranail\WaitlistKit\Commands\InstallCommand;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;
use Simtabi\Laranail\WaitlistKit\Database\Seeders\WaitlistKitSeeder;
use Simtabi\Laranail\WaitlistKit\View\Composers\AdminLayoutComposer;

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

    public const string LOGIN_LIMITER = 'laranail-waitlist-kit.admin-login';

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laranail/waitlist-kit')
            ->setPublishTagId('waitlist-kit')
            ->hasConfigFile('waitlist-kit')
            ->hasViews()
            ->hasAssets()
            ->hasBladeComponentNamespace('Simtabi\\Laranail\\WaitlistKit\\View\\Components', 'laranail-waitlist-kit')
            ->hasTranslations()
            ->hasRoutesWhen('laranail.waitlist-kit.routes.enabled', 'web', true)
            ->hasRoutesWhen('laranail.waitlist-kit.admin.enabled', 'admin', true)
            ->hasCommands([AdminCommand::class, DoctorCommand::class, ExportCommand::class, ForgetCommand::class, InstallCommand::class])
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

        // Installed themes: the kit's own here; a theme package adds its own from its provider.
        $this->app->singleton(ThemeRegistry::class, static function (): ThemeRegistry {
            $registry = new ThemeRegistry;
            foreach (KitThemes::all() as $theme) {
                $registry->register($theme);
            }

            return $registry;
        });

        // The bundled sign-in's guard and user provider, added to the application's auth config under the kit's name.
        // Registered in both modes so switching auth.mode needs no other change; unused in host mode.
        $auth = $this->app->make(Repository::class);
        $auth->set('auth.guards.' . KitConfig::BUNDLED_GUARD, ['driver' => 'session', 'provider' => KitConfig::BUNDLED_GUARD]);
        $auth->set('auth.providers.' . KitConfig::BUNDLED_GUARD, ['driver' => 'eloquent', 'model' => Admin::class]);

        $this->app->bind(KitConfig::class, static fn (Application $app): KitConfig => new KitConfig($app->make(Repository::class)));
    }

    #[Override]
    public function packageBooted(): void
    {
        // Configuration, not request state: the same for every request a long-running worker serves.
        $config = $this->app->make(KitConfig::class);
        Tables::configure($config->tablePrefix(), $config->connection());

        $this->defineAbilities($config);

        View::composer(['laranail/waitlist-kit::admin.layout', 'laranail/waitlist-kit::admin.login'], AdminLayoutComposer::class);

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

        // Five sign-in attempts per address per minute, so the bundled login cannot be guessed at speed.
        RateLimiter::for(self::LOGIN_LIMITER, static fn (Request $request): Limit => Limit::perMinute(5)->by(mb_strtolower($request->string('email')->toString()) . '|' . $request->ip()));

        RateLimiter::for(self::ENQUIRY_LIMITER, static fn (Request $request): Limit => Limit::perMinute($config->enquiriesPerMinute())->by((string) $request->ip()));
    }

    /**
     * The backend's abilities, each only when the application has not defined it: an application's own definition
     * always wins. Bundled administrators hold all four. In host mode the default admits the addresses in
     * admin.emails, and nobody else.
     */
    private function defineAbilities(KitConfig $config): void
    {
        foreach (Abilities::ALL as $ability) {
            if (Gate::has($ability)) {
                continue;
            }

            Gate::define($ability, static function (mixed $user) use ($config): bool {
                if ($user instanceof Admin) {
                    return true;
                }

                $email = is_object($user) && isset($user->email) && is_string($user->email) ? mb_strtolower($user->email) : null;

                return $email !== null && in_array($email, $config->adminEmails(), true);
            });
        }
    }
}
