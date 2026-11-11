<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Simtabi\Laranail\WaitlistKit\Enums\AdminAppearance;
use Illuminate\Contracts\Cookie\QueueingFactory as Cookies;
use Simtabi\Laranail\WaitlistKit\Http\Requests\Admin\AppearanceRequest;

/** Saves the backend's colour scheme for this browser and returns to the screen it was chosen on. */
final readonly class AppearanceController
{
    public function __construct(private Cookies $cookies) {}

    public function update(AppearanceRequest $request): RedirectResponse
    {
        $this->cookies->queue($this->cookies->make(AdminAppearance::COOKIE, $request->appearance()->value, AdminAppearance::COOKIE_MINUTES));

        return redirect()->to($request->returnPath());
    }
}
