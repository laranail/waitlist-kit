<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Actions\SubmitEnquiry;
use Simtabi\Laranail\WaitlistKit\Http\Requests\ContactRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** `POST /{prefix}/{api}/enquiries`: the contact form's endpoint for a headless front end; 404 while contact is off. */
final readonly class EnquiryController
{
    public function __construct(private KitConfig $config) {}

    public function __invoke(ContactRequest $request, SubmitEnquiry $submit): JsonResponse
    {
        if (! $this->config->contactEnabled()) {
            throw new NotFoundHttpException;
        }

        $submit->handle($request->toData(), Actor::from($request->user()));

        $response = new JsonResponse(['message' => __('laranail/waitlist-kit::contact.sent')], 201);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
