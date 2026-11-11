<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\WaitlistKit\Http\Middleware\AuthenticateAdmin;
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
 */

$config = config('laranail.waitlist-kit.admin');
$config = is_array($config) ? $config : [];
Route::middleware($config['middleware'] ?? ['web'])
    ->prefix(is_string($config['prefix'] ?? null) ? $config['prefix'] : 'waitlist-admin')
    ->name('laranail-waitlist-kit.admin.')
    ->group(static function (): void {
        // Registered in both sign-in modes, so the route list never depends on auth.mode; the controller answers 404
        // unless the mode is bundled.
        Route::get('login', [LoginController::class, 'show'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->middleware('throttle:laranail-waitlist-kit.admin-login')->name('login.store');
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

        Route::middleware(AuthenticateAdmin::class)->group(static function (): void {
            Route::get('/', [DashboardController::class, 'show'])->name('dashboard');
            Route::post('appearance', [AppearanceController::class, 'update'])->name('appearance.update');

            Route::get('campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
            Route::get('campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
            Route::post('campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
            Route::get('campaigns/{campaign}/edit', [CampaignController::class, 'edit'])->name('campaigns.edit');
            Route::put('campaigns/{campaign}', [CampaignController::class, 'update'])->name('campaigns.update');
            Route::put('campaigns/{campaign}/theme', [CampaignController::class, 'theme'])->name('campaigns.theme');
            Route::put('campaigns/{campaign}/conversion', [CampaignController::class, 'conversion'])->name('campaigns.conversion');
            Route::get('campaigns/{campaign}/funnel', [CampaignController::class, 'funnel'])->name('campaigns.funnel');
            Route::delete('campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');

            Route::post('campaigns/{campaign}/blocks', [BlockController::class, 'storeForCampaign'])->name('campaigns.blocks.store');
            Route::post('pages/{page}/blocks', [BlockController::class, 'storeForPage'])->name('pages.blocks.store');
            Route::put('blocks/{block}', [BlockController::class, 'update'])->name('blocks.update');
            Route::post('blocks/{block}/move', [BlockController::class, 'move'])->name('blocks.move');
            Route::delete('blocks/{block}', [BlockController::class, 'destroy'])->name('blocks.destroy');

            Route::get('campaigns/{campaign}/subscribers', [SubscriberController::class, 'index'])->name('subscribers.index');
            Route::get('campaigns/{campaign}/subscribers/export', [SubscriberController::class, 'export'])->name('subscribers.export');
            Route::delete('campaigns/{campaign}/subscribers/{subscriber}', [SubscriberController::class, 'destroy'])->name('subscribers.destroy');
            Route::post('campaigns/{campaign}/invites', [SubscriberController::class, 'invite'])->name('subscribers.invite');
            Route::post('forget', [SubscriberController::class, 'forget'])->name('forget');

            Route::get('enquiries', [EnquiryController::class, 'index'])->name('enquiries.index');
            Route::get('enquiries/{enquiry}', [EnquiryController::class, 'show'])->name('enquiries.show');

            Route::get('pages', [PageController::class, 'index'])->name('pages.index');
            Route::get('pages/{page}/edit', [PageController::class, 'edit'])->name('pages.edit');
            Route::put('pages/{page}', [PageController::class, 'update'])->name('pages.update');

            Route::get('settings', [SettingsController::class, 'show'])->name('settings.show');
            Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');

            Route::get('integrations', [IntegrationController::class, 'index'])->name('integrations.index');
            Route::get('integrations/create', [IntegrationController::class, 'create'])->name('integrations.create');
            Route::post('integrations', [IntegrationController::class, 'store'])->name('integrations.store');
            Route::get('integrations/{integration}/edit', [IntegrationController::class, 'edit'])->name('integrations.edit');
            Route::put('integrations/{integration}', [IntegrationController::class, 'update'])->name('integrations.update');
            Route::delete('integrations/{integration}', [IntegrationController::class, 'destroy'])->name('integrations.destroy');
        });
    });
