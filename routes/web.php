<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\WaitlistKit\Http\Middleware\RejectHoneypot;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\SignupController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\CampaignController;

/*
 * The public routes, written out. Loaded only while laranail.waitlist-kit.routes.enabled is true; the prefix and
 * middleware come from the same config. Names carry the package prefix (laranail-waitlist-kit.*).
 */

$config = config('laranail.waitlist-kit.routes');
$config = is_array($config) ? $config : [];

Route::middleware($config['middleware'] ?? ['web'])
    ->prefix(is_string($config['prefix'] ?? null) ? $config['prefix'] : 'waitlist')
    ->name('laranail-waitlist-kit.')
    ->group(static function (): void {
        Route::get('{campaign:slug}', [CampaignController::class, 'show'])
            ->name('campaign.show');

        Route::post('{campaign:slug}', [SignupController::class, 'store'])
            ->middleware(['throttle:laranail-waitlist-kit.signups', RejectHoneypot::class])
            ->name('campaign.signup');
    });
