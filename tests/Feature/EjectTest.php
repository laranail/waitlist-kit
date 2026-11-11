<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Commands\EjectCommand;

/** Everything an eject writes into the Testbench application, removed again after each test. */
function ejectedPaths(): array
{
    return [
        'controllers' => app_path('Http/Controllers/WaitlistKit'),
        'routes'      => base_path('routes/waitlist-kit.php'),
        'web'         => base_path('routes/web.php'),
        'config'      => config_path('laranail/waitlist-kit.php'),
        'views'       => resource_path('views/vendor/laranail'),
    ];
}

beforeEach(function (): void {
    $paths = ejectedPaths();
    $this->webBefore = is_file($paths['web']) ? file_get_contents($paths['web']) : null;
    foreach (['controllers', 'routes', 'config', 'views'] as $key) {
        expect(file_exists($paths[$key]))->toBeFalse("{$paths[$key]} is left over from another run");
    }
    expect((string) $this->webBefore)->not->toContain(EjectCommand::REQUIRE_LINE);
});

afterEach(function (): void {
    $files = new Filesystem;
    $paths = ejectedPaths();
    $files->deleteDirectory($paths['controllers']);
    $files->deleteDirectory($paths['views']);
    $files->delete([$paths['routes'], $paths['config']]);
    $this->webBefore === null ? $files->delete($paths['web']) : $files->put($paths['web'], $this->webBefore);
});

it('copies the views, controllers and routes into the application and turns the kit routes off', function (): void {
    $this->artisan('laranail::waitlist-kit.eject')->assertSuccessful();

    $paths = ejectedPaths();
    $controllers = glob($paths['controllers'] . '/*.php') ?: [];
    $kitControllers = glob(dirname(__DIR__, 2) . '/src/Http/Controllers/*.php') ?: [];

    expect($kitControllers)->toHaveCount(10)
        ->and(array_map(basename(...), $controllers))->toBe(array_map(basename(...), $kitControllers));
    foreach ($controllers as $controller) {
        expect(file_get_contents($controller))
            ->toContain('namespace ' . EjectCommand::APP_NAMESPACE . ';')
            ->not->toContain('namespace ' . EjectCommand::PACKAGE_NAMESPACE . ';');
    }

    expect(file_get_contents($paths['routes']))
        ->toContain('use ' . EjectCommand::APP_NAMESPACE . '\\CampaignController;')
        ->not->toContain(EjectCommand::PACKAGE_NAMESPACE)
        ->and(file_get_contents($paths['config']))->toContain(EjectCommand::ROUTES_OFF)->not->toContain(EjectCommand::ROUTES_ON)
        ->and(substr_count((string) file_get_contents($paths['web']), EjectCommand::REQUIRE_LINE))->toBe(1)
        ->and(is_file($paths['views'] . '/waitlist-kit/base/campaign.blade.php'))->toBeTrue();
});

it('refuses to eject twice unless forced, and adds the require line once', function (): void {
    $this->artisan('laranail::waitlist-kit.eject')->assertSuccessful();

    $this->artisan('laranail::waitlist-kit.eject')
        ->expectsOutputToContain('Already ejected')
        ->assertFailed();

    $this->artisan('laranail::waitlist-kit.eject', ['--force' => true])->assertSuccessful();
    expect(substr_count((string) file_get_contents(ejectedPaths()['web']), EjectCommand::REQUIRE_LINE))->toBe(1);
});

it('stops before writing when the published config has lost its route switch', function (): void {
    $paths = ejectedPaths();
    (new Filesystem)->ensureDirectoryExists(dirname($paths['config']));
    file_put_contents($paths['config'], "<?php\n\nreturn ['routes' => ['enabled' => true]];\n");

    $this->artisan('laranail::waitlist-kit.eject')
        ->expectsOutputToContain('no longer contains')
        ->assertFailed();

    expect(file_exists($paths['controllers']))->toBeFalse()
        ->and(file_exists($paths['routes']))->toBeFalse();
});

it('serves the campaign page through the ejected controller', function (): void {
    Campaign::factory()->live()->create(['slug' => 'launch', 'name' => 'Northwind']);
    $this->artisan('laranail::waitlist-kit.eject')->assertSuccessful();

    $paths = ejectedPaths();
    foreach (glob($paths['controllers'] . '/*.php') ?: [] as $controller) {
        require_once $controller;
    }
    require $paths['routes'];
    Route::getRoutes()->refreshNameLookups();

    expect(Route::getRoutes()->getByName('laranail-waitlist-kit.campaign.show')?->getActionName())
        ->toBe(EjectCommand::APP_NAMESPACE . '\\CampaignController@show');
    $this->get('/waitlist/launch')->assertOk()->assertSee('Northwind');
});
