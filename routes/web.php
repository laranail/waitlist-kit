<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Simtabi\Laranail\WaitlistKit\Http\Middleware\RejectHoneypot;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\PageController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\AccessController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\SignupController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\StatusController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\ConfirmController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\ContactController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\SitemapController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\CampaignController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\ConversionController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\UnsubscribeController;

/*
 * The public routes, written out, all at /{routes.prefix} (`waitlist` by default) and named laranail-waitlist-kit.*.
 * Loaded only while laranail.waitlist-kit.routes.enabled is true. The fixed paths (contact, pages, sitemap.xml, api)
 * come before the campaign slug, and CampaignRules reserves those words as slugs.
 *
 * The two forms that validate through a FormRequest (sign-up, contact) also answer Laravel Precognition: a request
 * carrying `Precognition: true` is validated and answered 204 or 422 without running the controller, which is what
 * the forms' live validation sends. Their limiters give those requests their own bucket (see the provider).
 */

$config = config('laranail.waitlist-kit.routes');
$config = is_array($config) ? $config : [];
$prefix = is_string($config['prefix'] ?? null) ? $config['prefix'] : 'waitlist';

Route::prefix($prefix)->name('laranail-waitlist-kit.')->group(static function () use ($config): void {
    // Browser-facing pages and forms: the `web` group (session, CSRF).
    Route::middleware($config['middleware'] ?? ['web'])->group(static function (): void {
        Route::post('contact', [ContactController::class, 'store'])
            ->middleware([HandlePrecognitiveRequests::class, 'throttle:laranail-waitlist-kit.enquiries', RejectHoneypot::class . ':contact'])
            ->name('contact.store');

        Route::get('pages/{page:slug}', [PageController::class, 'show'])->name('page.show');

        Route::get('sitemap.xml', SitemapController::class)->name('sitemap');

        // One campaign: its page, its sign-up and everything a subscriber's links lead to.
        Route::prefix('{campaign:slug}')->name('campaign.')->group(static function (): void {
            Route::get('/', [CampaignController::class, 'show'])->name('show');

            Route::post('/', [SignupController::class, 'store'])
                ->middleware([HandlePrecognitiveRequests::class, 'throttle:laranail-waitlist-kit.signups', RejectHoneypot::class . ':signup'])
                ->name('signup');

            // Double opt-in: the signed, expiring link from ConfirmMail; the subscriber must belong to the campaign.
            Route::get('confirm/{subscriber}', ConfirmController::class)
                ->middleware(ValidateSignature::class)
                ->scopeBindings()
                ->name('confirm');

            // Early access: an invitation code sends its holder on to the product; throttled.
            Route::post('access', AccessController::class)
                ->middleware('throttle:laranail-waitlist-kit.access')
                ->name('access');

            // A subscriber's own page, keyed by their unguessable referral code; linked from the welcome mail only.
            Route::get('status/{code}', StatusController::class)
                ->where('code', '[a-z0-9]{6,32}')
                ->name('status');

            // The signed unsubscribe link in every mail. GET asks; POST leaves, and takes a mail client's one-click
            // unsubscribe (RFC 8058), which carries no CSRF token: the signature is what proves the request.
            Route::prefix('unsubscribe/{subscriber}')->middleware(ValidateSignature::class)->scopeBindings()->group(static function (): void {
                Route::get('/', [UnsubscribeController::class, 'show'])->name('unsubscribe');
                Route::post('/', [UnsubscribeController::class, 'store'])
                    ->withoutMiddleware([PreventRequestForgery::class, ValidateCsrfToken::class])
                    ->name('unsubscribe.store');
            });
        });
    });

    // The conversion endpoint the product calls: no session, cookies or CSRF (it authenticates with a bearer token),
    // and throttled per caller. `api` is a reserved campaign slug.
    Route::post('api/conversions', [ConversionController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('conversions.store');
});
