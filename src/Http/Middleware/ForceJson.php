<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The API answers JSON whatever the client's Accept header says, so a validation error is a 422 body rather than a
 * redirect, and an exception is never an HTML page.
 */
final class ForceJson
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        /** @var Response */
        return $next($request);
    }
}
