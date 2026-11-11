<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Actions\ConvertSubscriber;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * `POST /{prefix}/api/conversions` with `Authorization: Bearer <conversion.token>` and `{"campaign": slug,
 * "invite": code}`: the product reports that an invited person used their access. Without a configured token the
 * endpoint does not exist (404), so it fails closed. 204 whether or not it changed anything, 404 for an unknown code.
 */
final readonly class ConversionController
{
    public function __construct(private KitConfig $config) {}

    public function store(Request $request, ConvertSubscriber $convert): JsonResponse
    {
        $token = $this->config->conversionToken() ?? throw new NotFoundHttpException;
        if (! hash_equals($token, (string) $request->bearerToken())) {
            return new JsonResponse(['message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'campaign' => ['required', 'string', 'max:120'],
            'invite'   => ['required', 'string', 'regex:/^[a-z0-9]{6,32}$/'],
        ]);

        $campaign = Campaign::query()->where('slug', $request->string('campaign')->toString())->first() ?? throw new NotFoundHttpException;
        $subscriber = Subscriber::query()->where('campaign_id', $campaign->id)->where('referral_code', $request->string('invite')->toString())->first()
            ?? throw new NotFoundHttpException;

        $convert->handle($subscriber);

        return new JsonResponse(null, 204);
    }
}
