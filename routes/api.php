<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Simtabi\Laranail\WaitlistKit\Support\ApiAbilities;
use Simtabi\Laranail\WaitlistKit\Http\Middleware\ForceJson;
use Simtabi\Laranail\WaitlistKit\Http\Middleware\AuthenticateApi;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Simtabi\Laranail\WaitlistKit\Http\Middleware\RequireTokenAbility;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\Api\SignupController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\Api\StatusController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\Api\CaptchaController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\Api\EnquiryController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\Api\CampaignController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\Api\Manage\EnquiryController as ManageEnquiries;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\Api\Manage\CampaignController as ManageCampaigns;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\Api\Manage\SubscriberController as ManageSubscribers;

/*
 * The REST API, written out: /{routes.prefix}/{api.prefix} (/waitlist/api/v1 by default), named
 * laranail-waitlist-kit.api.*. Loaded while laranail.waitlist-kit.api.enabled is true. JSON in and out, no session.
 *
 * Public: a campaign, the sign-up and contact endpoints (the forms' own requests, rules, captcha and throttles), a
 * subscriber's status by code, and the captcha to show. `manage/*`: everything the backend does, behind api.guard
 * (Sanctum by default), each request checked against the kit's policies and, with a token, its ability.
 */

$routes = config('laranail.waitlist-kit.routes');
$api = config('laranail.waitlist-kit.api');
$prefix = trim((is_array($routes) && is_string($routes['prefix'] ?? null) ? $routes['prefix'] : 'waitlist') . '/' . (is_array($api) && is_string($api['prefix'] ?? null) ? $api['prefix'] : 'api/v1'), '/');

// No `web` or `api` group (no session, no CSRF), so route-model binding is asked for here.
Route::prefix($prefix)->name('laranail-waitlist-kit.api.')->middleware([ForceJson::class, SubstituteBindings::class])->group(static function (): void {
    Route::get('captcha', CaptchaController::class)->middleware('throttle:laranail-waitlist-kit.api')->name('captcha');

    Route::post('enquiries', EnquiryController::class)
        ->middleware([HandlePrecognitiveRequests::class, 'throttle:laranail-waitlist-kit.enquiries'])
        ->name('enquiries.store');

    Route::prefix('campaigns/{campaign:slug}')->name('campaigns.')->group(static function (): void {
        Route::get('/', [CampaignController::class, 'show'])->middleware('throttle:laranail-waitlist-kit.api')->name('show');
        Route::post('subscribers', SignupController::class)
            ->middleware([HandlePrecognitiveRequests::class, 'throttle:laranail-waitlist-kit.signups'])
            ->name('subscribers.store');
        Route::get('status/{code}', StatusController::class)
            ->where('code', '[a-z0-9]{6,32}')
            ->middleware('throttle:laranail-waitlist-kit.api')
            ->name('status');
    });

    Route::prefix('manage')->name('manage.')->middleware([AuthenticateApi::class, 'throttle:laranail-waitlist-kit.api'])->group(static function (): void {
        $read = RequireTokenAbility::class . ':' . ApiAbilities::READ;
        $write = RequireTokenAbility::class . ':' . ApiAbilities::WRITE;

        Route::prefix('campaigns')->name('campaigns.')->controller(ManageCampaigns::class)->group(static function () use ($read, $write): void {
            Route::get('/', 'index')->middleware($read)->name('index');
            Route::post('/', 'store')->middleware($write)->name('store');
            Route::get('{campaign}', 'show')->middleware($read)->name('show');
            Route::match(['put', 'patch'], '{campaign}', 'update')->middleware($write)->name('update');
            Route::delete('{campaign}', 'destroy')->middleware(RequireTokenAbility::class . ':' . ApiAbilities::ERASE)->name('destroy');
            Route::get('{campaign}/funnel', 'funnel')->middleware($read)->name('funnel');
        });

        Route::prefix('campaigns/{campaign}')->controller(ManageSubscribers::class)->scopeBindings()->group(static function () use ($read, $write): void {
            Route::get('subscribers', 'index')->middleware($read)->name('subscribers.index');
            Route::post('subscribers', 'store')->middleware($write)->name('subscribers.store');
            Route::get('subscribers/export', 'export')->middleware(RequireTokenAbility::class . ':' . ApiAbilities::EXPORT)->name('subscribers.export');
            Route::get('subscribers/{subscriber}', 'show')->middleware($read)->name('subscribers.show');
            Route::delete('subscribers/{subscriber}', 'destroy')->middleware($write)->name('subscribers.destroy');
            Route::post('subscribers/{subscriber}/convert', 'convert')->middleware($write)->name('subscribers.convert');
            Route::post('invites', 'invite')->middleware($write)->name('invites.store');
        });

        Route::post('forget', [ManageSubscribers::class, 'forget'])->middleware(RequireTokenAbility::class . ':' . ApiAbilities::ERASE)->name('forget');

        Route::prefix('enquiries')->name('enquiries.')->controller(ManageEnquiries::class)->group(static function () use ($read): void {
            Route::get('/', 'index')->middleware($read)->name('index');
            Route::get('{enquiry}', 'show')->middleware($read)->name('show');
        });
    });
});
