<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Actions\SubmitEnquiry;
use Simtabi\Laranail\WaitlistKit\Http\Requests\ContactRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * `POST /{prefix}/contact`: the headless contact endpoint. A page's script (`Accept: application/json`) gets 201;
 * a form without JavaScript returns to its `return` path on this site with a flash.
 */
final readonly class ContactController
{
    public const string FLASH = 'laranail/waitlist-kit.enquiry-sent';

    public function __construct(private KitConfig $config) {}

    public static function accepted(bool $json, string $returnPath): JsonResponse|RedirectResponse
    {
        $response = $json
            ? new JsonResponse(['message' => __('laranail/waitlist-kit::contact.sent')], 201)
            : redirect()->to($returnPath)->with(self::FLASH, true);

        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    public function store(ContactRequest $request, SubmitEnquiry $submit): JsonResponse|RedirectResponse
    {
        if (! $this->config->contactEnabled()) {
            throw new NotFoundHttpException;
        }

        $submit->handle($request->toData(), Actor::from($request->user()));

        return self::accepted($request->expectsJson(), $request->returnPath());
    }
}
