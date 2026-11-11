<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Middleware\ValidateSignature;
use Simtabi\Laranail\WaitlistKit\Http\Middleware\RejectHoneypot;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\PageController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\SignupController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\StatusController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\ConfirmController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\ContactController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\CampaignController;

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

        Route::post('{campaign:slug}', [SignupController::class, 'store'])
            ->middleware(['throttle:laranail-waitlist-kit.signups', RejectHoneypot::class . ':signup'])
            ->name('campaign.signup');
    });
