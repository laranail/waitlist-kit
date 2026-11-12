<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Actions\SubscribeToCampaign;
use Simtabi\Laranail\WaitlistKit\Http\Requests\SignupRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * `POST /{prefix}/{campaign}`: resolve, delegate, answer. A new address and a repeat get the same answer, so the form
 * cannot be used to ask who signed up. The page's script (`Accept: application/json`) gets 201; a form posted without
 * JavaScript goes back to the campaign's page with a flash.
 */
final readonly class SignupController
{
    public const string FLASH = 'laranail/waitlist-kit.signed-up';

    /** The one answer every accepted post gets, from here and from the honeypot. Never cached. */
    public static function accepted(Request $request, Campaign $campaign): JsonResponse|RedirectResponse
    {
        $response = $request->expectsJson()
            ? new JsonResponse(['message' => self::message($campaign)], 201)
            : redirect()->route('laranail-waitlist-kit.campaign.show', $campaign)->with(self::FLASH, true);

        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    /** "You're on the list", or, while the campaign asks for confirmation, "check your email": the same for everyone. */
    public static function message(Campaign $campaign): string
    {
        return __($campaign->conversion()->doubleOptIn ? 'laranail/waitlist-kit::page.check_email' : 'laranail/waitlist-kit::page.sent');
    }

    public function store(SignupRequest $request, Campaign $campaign, SubscribeToCampaign $subscribe): JsonResponse|RedirectResponse
    {
        if (! $campaign->acceptsSignups()) {
            throw new NotFoundHttpException;
        }

        $subscribe->handle($campaign, $request->toData(), Actor::from($request->user()));

        return self::accepted($request, $campaign);
    }
}
