<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers\Api\Manage;

use Illuminate\Http\Request;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Simtabi\Laranail\WaitlistKit\Models\Enquiry;
use Simtabi\Laranail\WaitlistKit\Services\Authorizer;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Simtabi\Laranail\WaitlistKit\Http\Requests\Api\ListRequest;
use Simtabi\Laranail\WaitlistKit\Http\Resources\EnquiryResource;

/** `manage/enquiries`: the contact enquiries, newest first, for the people who answer them (EnquiryPolicy). */
final readonly class EnquiryController
{
    public function __construct(private Authorizer $authorizer) {}

    public function index(ListRequest $request): AnonymousResourceCollection
    {
        $this->authorizer->authorize(Actor::from($request->user()), 'viewAny', Enquiry::class);

        return EnquiryResource::collection(Enquiry::query()->orderByDesc('created_at')->orderByDesc('id')->paginate($request->perPage()));
    }

    public function show(Request $request, Enquiry $enquiry): EnquiryResource
    {
        $this->authorizer->authorize(Actor::from($request->user()), 'view', $enquiry);

        return new EnquiryResource($enquiry);
    }
}
