<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Providers;

use Override;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Event;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\RateLimiter;
use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\WaitlistKit\Models\Page;
use Simtabi\Laranail\WaitlistKit\Models\Admin;
use Illuminate\Contracts\Foundation\Application;
use Simtabi\Laranail\WaitlistKit\Auth\Abilities;
use Simtabi\Laranail\WaitlistKit\Models\Enquiry;
use Simtabi\Laranail\WaitlistKit\Support\Tables;
use Simtabi\Laranail\Settings\Contracts\Settings;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Support\MorphMap;
use Simtabi\Laranail\WaitlistKit\Themes\KitThemes;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Illuminate\Database\Eloquent\Relations\Relation;
use Simtabi\Laranail\WaitlistKit\Models\Integration;
use Simtabi\Laranail\WaitlistKit\Policies\PagePolicy;
use Simtabi\Laranail\WaitlistKit\Settings\ConfigStore;
use Simtabi\Laranail\WaitlistKit\Themes\ThemeRegistry;
use Simtabi\Laranail\WaitlistKit\Commands\AdminCommand;
use Simtabi\Laranail\WaitlistKit\Commands\EjectCommand;
use Simtabi\Laranail\WaitlistKit\Commands\DoctorCommand;
use Simtabi\Laranail\WaitlistKit\Commands\ExportCommand;
use Simtabi\Laranail\WaitlistKit\Commands\ForgetCommand;
use Simtabi\Laranail\WaitlistKit\Events\EnquiryReceived;
use Simtabi\Laranail\WaitlistKit\Policies\EnquiryPolicy;
use Simtabi\Laranail\WaitlistKit\Settings\SettingsStore;
use Simtabi\Laranail\WaitlistKit\Commands\InstallCommand;
use Simtabi\Laranail\WaitlistKit\Policies\CampaignPolicy;
use Simtabi\Laranail\WaitlistKit\Events\SubscriberSignedUp;
use Simtabi\Laranail\WaitlistKit\Listeners\SendSignupAlert;
use Simtabi\Laranail\WaitlistKit\Policies\SubscriberPolicy;
use Simtabi\Laranail\WaitlistKit\Events\SubscriberConfirmed;
use Simtabi\Laranail\WaitlistKit\Listeners\SendEnquiryAlert;
use Simtabi\Laranail\WaitlistKit\Policies\IntegrationPolicy;
use Simtabi\Laranail\WaitlistKit\Listeners\SendMilestoneAlert;
use Simtabi\Laranail\WaitlistKit\Listeners\CheckListMilestones;
use Simtabi\Laranail\WaitlistKit\Settings\LaranailSettingsStore;
use Simtabi\Laranail\WaitlistKit\Events\CampaignMilestoneReached;
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

    /** Early-access code attempts, per campaign and visitor. */
    public const string ACCESS_LIMITER = 'laranail-waitlist-kit.access';

    public const int ACCESS_PER_MINUTE = 10;

    /** The kit's policies, each registered only when the application has none of its own for the model. */
    public const array POLICIES = [
        Campaign::class    => CampaignPolicy::class,
        Subscriber::class  => SubscriberPolicy::class,
        Enquiry::class     => EnquiryPolicy::class,
        Integration::class => IntegrationPolicy::class,
        Page::class        => PagePolicy::class,
    ];

    /** The kit's own listeners. Each checks its own switch (alerts.*) when it runs, so config changes need no reboot. */
    public const array LISTENERS = [
        SubscriberConfirmed::class      => [CheckListMilestones::class],
        SubscriberSignedUp::class       => [SendSignupAlert::class],
        EnquiryReceived::class          => [SendEnquiryAlert::class],
        CampaignMilestoneReached::class => [SendMilestoneAlert::class],
    ];

    /** Live-validation requests per form per visitor per minute; they validate and store nothing. */
    public const int PRECOGNITION_PER_MINUTE = 60;

    public const string LOGIN_LIMITER = 'laranail-waitlist-kit.admin-login';

    /** laranail/settings' contract, bound only when that package is installed. */
    public const string LARANAIL_SETTINGS = Settings::class;

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
            ->hasCommands([AdminCommand::class, DoctorCommand::class, EjectCommand::class, ExportCommand::class, ForgetCommand::class, InstallCommand::class])
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

        // The editable settings' store, chosen once here: laranail/settings when asked for and installed, else the
        // configuration alone. A missing package with store = database is the doctor's to report, not a boot failure.
        $this->app->scoped(SettingsStore::class, static function (Application $app): SettingsStore {
            $wanted = $app->make(Repository::class)->get('laranail.waitlist-kit.settings.store');

            return $wanted === 'database' && $app->bound(self::LARANAIL_SETTINGS)
                ? new LaranailSettingsStore($app->make(self::LARANAIL_SETTINGS))
                : new ConfigStore;
        });

        $this->app->bind(KitConfig::class, static fn (Application $app): KitConfig => new KitConfig($app->make(Repository::class), $app->make(SettingsStore::class)));
    }

    #[Override]
    public function packageBooted(): void
    {
        // Table names: configuration only (never an editable setting), so they are the same for every request a
        // long-running worker serves. Everything below reads KitConfig per call instead: an editable setting can
        // change between requests, and its store is request-scoped.
        $config = $this->app->make(KitConfig::class);
        Tables::configure($config->tablePrefix(), $config->connection());
        $app = $this->app;

        $this->defineAbilities();
        $this->registerPolicies();
        $this->registerListeners();

        View::composer(['laranail/waitlist-kit::admin.layout', 'laranail/waitlist-kit::admin.login'], AdminLayoutComposer::class);

        // Merged, never replacing the application's own map, and never enforced globally (see MorphMap).
        Relation::morphMap(MorphMap::MAP);

        // Sign-ups per campaign per visitor. `$request->ip()` honours the application's TrustProxies, so behind
        // Cloudflare or a load balancer each visitor has their own limit once the proxy is trusted there.
        RateLimiter::for(self::SIGNUP_LIMITER, static function (Request $request) use ($app): Limit {
            // The raw slug: Laravel's middleware priority runs throttling before route-model binding.
            $slug = $request->route()?->originalParameter('campaign');

            $key = (is_string($slug) ? $slug : '') . '|' . $request->ip();

            // Live validation (Precognition) asks on every changed field, and never stores anything: its own, wider
            // bucket, so typing an address cannot use up the visitor's sign-ups.
            return $request->isAttemptingPrecognition()
                ? Limit::perMinute(self::PRECOGNITION_PER_MINUTE)->by('precognition|' . $key)
                : Limit::perMinute($app->make(KitConfig::class)->signupsPerMinute())->by($key);
        });

        // Five sign-in attempts per address per minute, so the bundled login cannot be guessed at speed.
        RateLimiter::for(self::LOGIN_LIMITER, static fn (Request $request): Limit => Limit::perMinute(5)->by(mb_strtolower($request->string('email')->toString()) . '|' . $request->ip()));

        RateLimiter::for(self::ACCESS_LIMITER, static function (Request $request): Limit {
            $slug = $request->route('campaign');
            $slug = $slug instanceof Campaign ? $slug->slug : (is_string($slug) ? $slug : '');

            return Limit::perMinute(self::ACCESS_PER_MINUTE)->by($slug . '|' . $request->ip());
        });
        RateLimiter::for(self::ENQUIRY_LIMITER, static fn (Request $request): Limit => $request->isAttemptingPrecognition()
            ? Limit::perMinute(self::PRECOGNITION_PER_MINUTE)->by('precognition|' . $request->ip())
            : Limit::perMinute($app->make(KitConfig::class)->enquiriesPerMinute())->by((string) $request->ip()));
    }

    /**
     * The backend's abilities, each only when the application has not defined it: an application's own definition
     * always wins. Bundled administrators hold all four. In host mode the default admits the addresses in
     * admin.emails, and nobody else.
     */
    private function defineAbilities(): void
    {
        $app = $this->app;

        foreach (Abilities::ALL as $ability) {
            if (Gate::has($ability)) {
                continue;
            }

            Gate::define($ability, static function (mixed $user) use ($app): bool {
                if ($user instanceof Admin) {
                    return true;
                }

                $email = is_object($user) && isset($user->email) && is_string($user->email) ? mb_strtolower($user->email) : null;

                return $email !== null && in_array($email, $app->make(KitConfig::class)->adminEmails(), true);
            });
        }
    }

    /**
     * The kit's policies, mapping model actions to the four abilities. An application's own policy for a model wins:
     * one registered with Gate::policy() is seen here and kept, so this never replaces it.
     */
    private function registerPolicies(): void
    {
        $policies = Gate::policies();
        foreach (self::POLICIES as $model => $policy) {
            if (! isset($policies[$model])) {
                Gate::policy($model, $policy);
            }
        }
    }

    private function registerListeners(): void
    {
        foreach (self::LISTENERS as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }
}
