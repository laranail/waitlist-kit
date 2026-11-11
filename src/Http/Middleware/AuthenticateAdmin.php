<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Middleware;

use Closure;
use LogicException;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Routing\UrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Simtabi\Laranail\WaitlistKit\Auth\Abilities;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Lets into the backend only someone signed in on its guard who holds `laranail-waitlist-kit.view`.
 *
 * A guest goes to the bundled login, or the application's `login` route in host mode (403 when it has none). The
 * guard becomes the request's default, so Gate checks and `auth()->user()` in the backend mean the administrator.
 */
final readonly class AuthenticateAdmin
{
    public function __construct(private KitConfig $config, private Auth $auth, private Gate $gate, private UrlGenerator $urls, private Router $router) {}

    public function handle(Request $request, Closure $next): Response
    {
        $guard = $this->config->adminGuard();
        $this->auth->shouldUse($guard);

        if (! $this->auth->guard($guard)->check()) {
            return $this->toLogin($request);
        }

        if (! $this->gate->allows(Abilities::VIEW)) {
            throw new AccessDeniedHttpException;
        }

        $response = $next($request);

        return $response instanceof Response ? $response : throw new LogicException('The backend must answer with a response.');
    }

    private function toLogin(Request $request): RedirectResponse
    {
        $request->session()->put('url.intended', $request->fullUrl());

        if ($this->config->adminGuard() === KitConfig::BUNDLED_GUARD) {
            return new RedirectResponse($this->urls->route('laranail-waitlist-kit.admin.login'));
        }

        if ($this->router->has('login')) {
            return new RedirectResponse($this->urls->route('login'));
        }

        throw new AccessDeniedHttpException;
    }
}
