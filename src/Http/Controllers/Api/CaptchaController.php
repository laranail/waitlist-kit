<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Simtabi\Laranail\WaitlistKit\Services\CaptchaGuard;

/**
 * `GET /{prefix}/{api}/captcha`: what a headless front end needs to render the configured captcha (any of
 * laranail/captcha's providers) and which field to post its token in. A Math or ALTCHA challenge is fresh each time,
 * so the answer is never cached.
 */
final readonly class CaptchaController
{
    public function __invoke(CaptchaGuard $captcha): JsonResponse
    {
        $response = new JsonResponse(['data' => $captcha->describe()]);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
