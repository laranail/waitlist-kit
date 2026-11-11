<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Validation\ValidationException;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** The bundled sign-in (auth.mode = bundled): a login form, throttled per address, and a logout. */
final readonly class LoginController
{
    public function __construct(private Auth $auth, private ViewFactory $views, private KitConfig $config) {}

    public function show(): View
    {
        $this->bundledOnly();

        return $this->views->make('laranail/waitlist-kit::admin.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->bundledOnly();

        $request->validate([
            'email'    => ['required', 'string', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:200'],
        ]);
        $credentials = ['email' => mb_strtolower($request->string('email')->toString()), 'password' => $request->string('password')->toString()];

        $guard = $this->auth->guard(KitConfig::BUNDLED_GUARD);
        if (! method_exists($guard, 'attempt') || ! $guard->attempt($credentials, $request->boolean('remember'))) {
            // The same message whether the address exists or not.
            throw ValidationException::withMessages(['email' => __('laranail/waitlist-kit::admin.login_failed')]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('laranail-waitlist-kit.admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->bundledOnly();

        $guard = $this->auth->guard(KitConfig::BUNDLED_GUARD);
        if (method_exists($guard, 'logout')) {
            $guard->logout();
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('laranail-waitlist-kit.admin.login');
    }

    /** In host mode the application signs people in; the kit's own login does not exist. */
    private function bundledOnly(): void
    {
        if ($this->config->adminGuard() !== KitConfig::BUNDLED_GUARD) {
            throw new NotFoundHttpException;
        }
    }
}
