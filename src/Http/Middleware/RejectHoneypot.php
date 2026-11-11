<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Middleware;

use Closure;
use LogicException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Support\ReturnPath;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\SignupController;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\ContactController;

/**
 * A field people never see, so only a script fills it in. Such a post gets exactly the answer a real sign-up gets, so
 * the script learns nothing, and nothing reaches validation or the database.
 */
final readonly class RejectHoneypot
{
    public function __construct(private KitConfig $config) {}

    /** @param  'signup'|'contact'  $form  which form's answer a caught script gets */
    public function handle(Request $request, Closure $next, string $form = 'signup'): Response
    {
        if ($request->filled($this->config->honeypotField())) {
            $campaign = $request->route('campaign');

            if ($form === 'contact') {
                return ContactController::accepted($request->expectsJson(), ReturnPath::from($request->string('return')->toString()));
            }

            if ($campaign instanceof Campaign) {
                return SignupController::accepted($request, $campaign);
            }
        }

        $response = $next($request);

        return $response instanceof Response ? $response : throw new LogicException('A sign-up must answer with a response.');
    }
}
