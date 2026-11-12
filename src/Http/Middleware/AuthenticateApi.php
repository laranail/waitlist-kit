<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Auth\Factory as Auth;
use Symfony\Component\HttpFoundation\Response;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;

/**
 * Signs `manage/*` requests in on api.guard (Sanctum by default) and makes it the request's guard, so `$request->user()`
 * and the Gate see that user. A guard the application does not define is a 503 that says so, never a 500: an install
 * that turned the API on without installing Sanctum learns why from the answer (and from the doctor).
 */
final readonly class AuthenticateApi
{
    public function __construct(private Auth $auth, private KitConfig $config, private Repository $appConfig) {}

    public function handle(Request $request, Closure $next): Response
    {
        $guard = $this->config->apiGuard();
        if (! is_array($this->appConfig->get('auth.guards.' . $guard))) {
            return new JsonResponse(['message' => sprintf('The waitlist API guard "%s" is not configured.', $guard)], 503);
        }

        if (! $this->auth->guard($guard)->check()) {
            return new JsonResponse(['message' => 'Unauthenticated.'], 401);
        }
        $this->auth->shouldUse($guard);

        /** @var Response */
        return $next($request);
    }
}
