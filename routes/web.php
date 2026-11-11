<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Simtabi\Laranail\WaitlistKit\Http\Middleware\RejectHoneypot;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\PageController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\SignupController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\StatusController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\ConfirmController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\ContactController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\CampaignController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\ConversionController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\UnsubscribeController;

/*
 * The public routes, written out. Loaded only while laranail.waitlist-kit.routes.enabled is true; the prefix and
 * middleware come from the same config. Names carry the package prefix (laranail-waitlist-kit.*). The fixed paths
 * (contact, pages) come before the campaign slug, and CampaignRules reserves those words as slugs.
 */

$config = config('laranail.waitlist-kit.routes');
$config = is_array($config) ? $config : [];

Route::middleware($config['middleware'] ?? ['web'])
    ->prefix(is_string($config['prefix'] ?? null) ? $config['prefix'] : 'waitlist')
    ->name('laranail-waitlist-kit.')
    ->group(static function (): void {
        Route::post('contact', [ContactController::class, 'store'])
            ->middleware(['throttle:laranail-waitlist-kit.enquiries', RejectHoneypot::class . ':contact'])
            ->name('contact.store');

        Route::get('pages/{page:slug}', [PageController::class, 'show'])
            ->name('page.show');

        Route::get('{campaign:slug}', [CampaignController::class, 'show'])
            ->name('campaign.show');

        // Double opt-in: the signed, expiring link from ConfirmMail; the subscriber must belong to the campaign.
        Route::get('{campaign:slug}/confirm/{subscriber}', ConfirmController::class)
            ->middleware(ValidateSignature::class)
            ->scopeBindings()
            ->name('campaign.confirm');

        // A subscriber's own page, keyed by their unguessable referral code; linked from the welcome mail only.
        Route::get('{campaign:slug}/status/{code}', StatusController::class)
            ->where('code', '[a-z0-9]{6,32}')
            ->name('campaign.status');

        // The signed unsubscribe link in every mail. GET asks; POST leaves, and takes a mail client's one-click
        // unsubscribe (RFC 8058), which carries no CSRF token: the signature is what proves the request.
        Route::get('{campaign:slug}/unsubscribe/{subscriber}', [UnsubscribeController::class, 'show'])
            ->middleware(ValidateSignature::class)
            ->scopeBindings()
            ->name('campaign.unsubscribe');
        Route::post('{campaign:slug}/unsubscribe/{subscriber}', [UnsubscribeController::class, 'store'])
            ->middleware(ValidateSignature::class)
            ->withoutMiddleware([PreventRequestForgery::class, ValidateCsrfToken::class])
            ->scopeBindings()
            ->name('campaign.unsubscribe.store');

        Route::post('{campaign:slug}', [SignupController::class, 'store'])
            ->middleware(['throttle:laranail-waitlist-kit.signups', RejectHoneypot::class . ':signup'])
            ->name('campaign.signup');
    });

// The conversion endpoint the product calls: no session, cookies or CSRF (it authenticates with a bearer token), and
// throttled per caller. Same prefix as the public routes; `api` is a reserved campaign slug.
Route::prefix(is_string($config['prefix'] ?? null) ? $config['prefix'] : 'waitlist')
    ->name('laranail-waitlist-kit.')
    ->middleware('throttle:60,1')
    ->group(static function (): void {
        Route::post('api/conversions', [ConversionController::class, 'store'])->name('conversions.store');
    });
