<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Commands;

use Override;
use SplFileInfo;
use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\Console\Tools\Commands\Command;
use Simtabi\Laranail\Console\Tools\Commands\Concerns\SupportsNamespacedNames;

/**
 * Copies the public side of the kit into the application, which then owns it: the views (into Laravel's override
 * directory), the public controllers (into App\Http\Controllers\WaitlistKit), and the public routes (into
 * routes/waitlist-kit.php, pointing at those controllers). The published config's route switch then defaults to off,
 * so the kit stops registering the routes the application now holds, and routes/web.php requires the new file. The backend, models and actions stay in the
 * package and keep updating with it.
 */
final class EjectCommand extends Command
{
    use SupportsNamespacedNames;

    public const string PACKAGE_NAMESPACE = 'Simtabi\\Laranail\\WaitlistKit\\Http\\Controllers';

    public const string APP_NAMESPACE = 'App\\Http\\Controllers\\WaitlistKit';

    /** The published config's route switch, before and after; replaced exactly once or the eject stops. */
    public const string ROUTES_ON = "env('WAITLIST_KIT_ROUTES', true)";

    /** The same switch as config published since headless mode (off by default when WAITLIST_KIT_HEADLESS is set). */
    public const string ROUTES_ON_HEADLESS_AWARE = "env('WAITLIST_KIT_ROUTES', ! env('WAITLIST_KIT_HEADLESS', false))";

    /** Every form of the switch an install may have published, newest first. */
    public const array ROUTES_ON_FORMS = [self::ROUTES_ON_HEADLESS_AWARE, self::ROUTES_ON];

    public const string ROUTES_OFF = "env('WAITLIST_KIT_ROUTES', false)";

    public const string REQUIRE_LINE = "require __DIR__ . '/waitlist-kit.php';";

    #[Override]
    protected $signature = 'laranail::waitlist-kit.eject {--force : Overwrite files an earlier eject wrote}';

    #[Override]
    protected $description = 'Copy the public views, controllers and routes into the application, which then owns them.';

    public function handle(Filesystem $files): int
    {
        $package = dirname(__DIR__, 2);
        $controllers = $this->laravel->path('Http/Controllers/WaitlistKit');
        $routes = $this->laravel->basePath('routes/waitlist-kit.php');
        $config = $this->laravel->configPath('laranail/waitlist-kit.php');
        $force = (bool) $this->option('force');

        $sources = $files->files($package . '/src/Http/Controllers');
        $targets = array_map(static fn (SplFileInfo $file): string => $controllers . '/' . $file->getFilename(), $sources);
        $existing = array_values(array_filter([...$targets, $routes], $files->exists(...)));
        if ($existing !== [] && ! $force) {
            $this->services->display()->error('Already ejected: ' . implode(', ', $existing) . '. Run with --force to overwrite.');

            return self::FAILURE;
        }

        // The config first: it is the one file edited in place, so a published config the eject cannot read stops
        // the eject before anything is written.
        if (! $files->exists($config)) {
            $this->callSilently('vendor:publish', ['--tag' => 'laranail::waitlist-kit-config']);
        }
        $contents = $files->get($config);
        $found = array_values(array_filter(self::ROUTES_ON_FORMS, static fn (string $form): bool => substr_count($contents, $form) === 1));
        if ($found === [] && ! str_contains($contents, self::ROUTES_OFF)) {
            $this->services->display()->error(sprintf('%s no longer contains %s; set routes.enabled to false there yourself, then run with --force.', $config, self::ROUTES_ON_HEADLESS_AWARE));

            return self::FAILURE;
        }
        if ($found !== []) {
            $files->put($config, str_replace($found[0], self::ROUTES_OFF, $contents));
        }
        $this->services->display()->success("Turned the kit's public routes off in {$config} (WAITLIST_KIT_ROUTES)");

        $this->callSilently('vendor:publish', array_filter(['--tag' => 'laranail::waitlist-kit-views', '--force' => $force]));
        $this->services->display()->success('Published the views to resources/views/vendor/laranail/waitlist-kit');

        $files->ensureDirectoryExists($controllers);
        foreach ($sources as $index => $source) {
            $files->put($targets[$index], $this->rehome($files->get($source->getPathname())));
        }
        $this->services->display()->success(sprintf('Copied %d controllers to %s', count($sources), $controllers));

        $files->ensureDirectoryExists(dirname($routes));
        $files->put($routes, $this->rehome($files->get($package . '/routes/web.php')));
        $this->services->display()->success("Wrote {$routes}");

        // routes/web.php loads the ejected file in one plain line, added once.
        $web = $this->laravel->basePath('routes/web.php');
        $current = $files->exists($web) ? $files->get($web) : "<?php\n";
        if (! str_contains($current, self::REQUIRE_LINE)) {
            $files->put($web, rtrim($current) . "\n\n" . self::REQUIRE_LINE . "\n");
            $this->services->display()->success("Loaded it from {$web}");
        }

        return self::SUCCESS;
    }

    /** Moves a controller, or a routes file's references to one, from the package's namespace to the application's. */
    private function rehome(string $php): string
    {
        return str_replace(
            ['namespace ' . self::PACKAGE_NAMESPACE . ';', 'use ' . self::PACKAGE_NAMESPACE . '\\'],
            ['namespace ' . self::APP_NAMESPACE . ';', 'use ' . self::APP_NAMESPACE . '\\'],
            $php,
        );
    }
}
