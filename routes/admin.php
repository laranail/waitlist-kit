<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\WaitlistKit\Http\Middleware\AuthenticateAdmin;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin\PageController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin\BlockController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin\LoginController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin\EnquiryController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin\CampaignController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin\SettingsController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin\DashboardController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin\AppearanceController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin\SubscriberController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin\IntegrationController;

/*
 * The backend, written out. Loaded while laranail.waitlist-kit.admin.enabled is true, at /{admin.prefix}, named
 * laranail-waitlist-kit.admin.*. Everything but the bundled login sits behind AuthenticateAdmin (signed in on the
 * backend's guard, holding laranail-waitlist-kit.view); changes are further checked per ability in their requests.
 *
 * The whole backend answers Laravel Precognition, so its forms validate each field as it changes (a request carrying
 * `Precognition: true` is validated and answered 204 or 422; the controller does not run). Routes are grouped by
 * what they act on, and every group adds its segment to both the path and the name.
 */

$config = config('laranail.waitlist-kit.admin');
$config = is_array($config) ? $config : [];
Route::middleware([...(array) ($config['middleware'] ?? ['web']), HandlePrecognitiveRequests::class])
    ->prefix(is_string($config['prefix'] ?? null) ? $config['prefix'] : 'admin/waitlist')
    ->name('laranail-waitlist-kit.admin.')
    ->group(static function (): void {
        // Registered in both sign-in modes, so the route list never depends on auth.mode; the controller answers 404
        // unless the mode is bundled.
        Route::controller(LoginController::class)->group(static function (): void {
            Route::get('login', 'show')->name('login');
            // No live validation here: every check would count against the five sign-in attempts a minute.
            Route::post('login', 'store')
                ->middleware('throttle:laranail-waitlist-kit.admin-login')
                ->withoutMiddleware(HandlePrecognitiveRequests::class)
                ->name('login.store');
            Route::post('logout', 'destroy')->name('logout');
        });

        Route::middleware(AuthenticateAdmin::class)->group(static function (): void {
            Route::get('/', [DashboardController::class, 'show'])->name('dashboard');
            Route::post('appearance', [AppearanceController::class, 'update'])->name('appearance.update');

            Route::prefix('campaigns')->group(static function (): void {
                Route::controller(CampaignController::class)->name('campaigns.')->group(static function (): void {
                    Route::get('/', 'index')->name('index');
                    Route::get('create', 'create')->name('create');
                    Route::post('/', 'store')->name('store');
                    Route::get('{campaign}/edit', 'edit')->name('edit');
                    Route::put('{campaign}', 'update')->name('update');
                    Route::put('{campaign}/theme', 'theme')->name('theme');
                    Route::put('{campaign}/conversion', 'conversion')->name('conversion');
                    Route::put('{campaign}/seo', 'seo')->name('seo');
                    Route::get('{campaign}/funnel', 'funnel')->name('funnel');
                    Route::delete('{campaign}', 'destroy')->name('destroy');
                });

                Route::post('{campaign}/blocks', [BlockController::class, 'storeForCampaign'])->name('campaigns.blocks.store');

                Route::controller(SubscriberController::class)->name('subscribers.')->group(static function (): void {
                    Route::get('{campaign}/subscribers', 'index')->name('index');
                    Route::get('{campaign}/subscribers/export', 'export')->name('export');
                    Route::delete('{campaign}/subscribers/{subscriber}', 'destroy')->name('destroy');
                    Route::post('{campaign}/invites', 'invite')->name('invite');
                });
            });

            Route::post('forget', [SubscriberController::class, 'forget'])->name('forget');

            Route::prefix('blocks/{block}')->name('blocks.')->controller(BlockController::class)->group(static function (): void {
                Route::put('/', 'update')->name('update');
                Route::post('move', 'move')->name('move');
                Route::delete('/', 'destroy')->name('destroy');
            });

            Route::prefix('enquiries')->name('enquiries.')->controller(EnquiryController::class)->group(static function (): void {
                Route::get('/', 'index')->name('index');
                Route::get('{enquiry}', 'show')->name('show');
            });

            Route::prefix('pages')->name('pages.')->group(static function (): void {
                Route::controller(PageController::class)->group(static function (): void {
                    Route::get('/', 'index')->name('index');
                    Route::get('{page}/edit', 'edit')->name('edit');
                    Route::put('{page}', 'update')->name('update');
                });
                Route::post('{page}/blocks', [BlockController::class, 'storeForPage'])->name('blocks.store');
            });

            Route::prefix('settings')->name('settings.')->controller(SettingsController::class)->group(static function (): void {
                Route::get('/', 'show')->name('show');
                Route::put('/', 'update')->name('update');
            });

            Route::prefix('integrations')->name('integrations.')->controller(IntegrationController::class)->group(static function (): void {
                Route::get('/', 'index')->name('index');
                Route::get('create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('{integration}/edit', 'edit')->name('edit');
                Route::put('{integration}', 'update')->name('update');
                Route::delete('{integration}', 'destroy')->name('destroy');
            });
        });
    });
