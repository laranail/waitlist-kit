<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;

/**
 * With a Sanctum token (any user exposing `tokenCan()`), the token must hold the endpoint's ability (ApiAbilities) as
 * well as the user passing the Gate, so a token can be narrower than its user. A session request through Sanctum
 * carries a transient token that can do anything, and a guard without tokens skips this: the Gate still decides.
 */
final readonly class RequireTokenAbility
{
    public function __construct(private KitConfig $config) {}

    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();
        if ($this->config->apiChecksTokenAbilities() && is_object($user) && method_exists($user, 'tokenCan') && $user->tokenCan($ability) !== true) {
            return new JsonResponse(['message' => sprintf('This token lacks the %s ability.', $ability)], 403);
        }

        /** @var Response */
        return $next($request);
    }
}
