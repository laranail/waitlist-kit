<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Actions\SubscribeToCampaign;
use Simtabi\Laranail\WaitlistKit\Http\Requests\SignupRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\SignupController as PageSignup;

/**
 * `POST /{prefix}/{api}/campaigns/{slug}/subscribers`: the sign-up form's endpoint for a headless front end. Same
 * request, rules, captcha, throttle and action as the page's, and the same answer for a new address and a repeat
 * (201 and a message), so the API cannot be used to learn who is on a list.
 */
final readonly class SignupController
{
    public function __invoke(SignupRequest $request, Campaign $campaign, SubscribeToCampaign $subscribe): JsonResponse
    {
        if (! $campaign->acceptsSignups()) {
            throw new NotFoundHttpException;
        }

        $subscribe->handle($campaign, $request->toData(), Actor::from($request->user()));

        $response = new JsonResponse(['message' => PageSignup::message($campaign)], 201);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
