<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * `POST /{prefix}/{slug}/access`: early access. Someone holding an invitation enters their code and is sent to the
 * campaign's invite URL with it; any other code (unknown, another campaign's, not yet invited) gets the same error, and
 * the route is throttled, so the form cannot be used to find codes. Only a live early-access campaign with an invite
 * URL has it. Posted by the page's script (JSON), it answers 422 with the same error, or 200 with where to go.
 */
final readonly class AccessController
{
    public const string ERROR_BAG = 'access';

    public function __invoke(Request $request, Campaign $campaign): JsonResponse|RedirectResponse
    {
        $inviteUrl = $campaign->conversion()->inviteUrl;
        if ($campaign->type !== CampaignType::EarlyAccess || ! $campaign->acceptsSignups() || $inviteUrl === null) {
            throw new NotFoundHttpException;
        }

        $code = mb_strtolower(trim($request->string('code')->toString()));
        $invited = preg_match('/^[a-z0-9]{6,32}$/', $code) === 1 && Subscriber::query()
            ->where('campaign_id', $campaign->id)
            ->where('referral_code', $code)
            ->whereIn('status', [SubscriberStatus::Invited, SubscriberStatus::Converted])
            ->exists();

        if (! $invited) {
            $message = __('laranail/waitlist-kit::page.access_invalid');

            return $request->expectsJson()
                ? new JsonResponse(['message' => $message, 'errors' => ['code' => [$message]]], 422)
                : redirect()->route('laranail-waitlist-kit.campaign.show', $campaign)->withErrors(['code' => $message], self::ERROR_BAG);
        }

        $destination = $inviteUrl . (str_contains($inviteUrl, '?') ? '&' : '?') . http_build_query(['invite' => $code], '', '&', PHP_QUERY_RFC3986);

        return $request->expectsJson() ? new JsonResponse(['redirect' => $destination]) : redirect()->away($destination);
    }
}
