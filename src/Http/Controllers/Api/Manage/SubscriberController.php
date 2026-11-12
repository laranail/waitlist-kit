<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers\Api\Manage;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Simtabi\Laranail\WaitlistKit\WaitlistKit;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Services\Authorizer;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Simtabi\Laranail\WaitlistKit\Exceptions\NoInviteUrl;
use Simtabi\Laranail\WaitlistKit\Actions\ForgetSubscriber;
use Simtabi\Laranail\WaitlistKit\Support\SubscriberExport;
use Simtabi\Laranail\WaitlistKit\Actions\ConvertSubscriber;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Simtabi\Laranail\WaitlistKit\Http\Requests\Api\ListRequest;
use Simtabi\Laranail\WaitlistKit\Http\Requests\Admin\ForgetRequest;
use Simtabi\Laranail\WaitlistKit\Http\Requests\Admin\InviteRequest;
use Simtabi\Laranail\WaitlistKit\Http\Resources\SubscriberResource;

/**
 * `manage/campaigns/{campaign}/subscribers` and friends: list, read, add, remove, convert, invite, export and forget.
 * Each is checked against SubscriberPolicy as the signed-in user; reads go through SubscriberQuery and writes through
 * the builders and actions, so the API holds no rule of its own.
 */
final readonly class SubscriberController
{
    public function __construct(private WaitlistKit $kit, private Authorizer $authorizer) {}

    public function index(ListRequest $request, Campaign $campaign): AnonymousResourceCollection
    {
        $query = $this->kit->subscribers($campaign)->as($request->user())->search($request->string('q')->toString());
        if ($request->filled('status')) {
            $query->status($request->string('status')->toString());
        }
        match ($request->string('order')->toString()) {
            'oldest'   => $query->oldest(),
            'position' => $query->byPosition(),
            default    => $query->latest(),
        };

        return SubscriberResource::collection($query->paginate($request->perPage()));
    }

    public function show(Request $request, Campaign $campaign, Subscriber $subscriber): SubscriberResource
    {
        $this->authorizer->authorize(Actor::from($request->user()), 'view', $subscriber);

        return new SubscriberResource($subscriber);
    }

    /** Adds an address as the backend would: once, through the sign-up rules, opt-in and welcome included. */
    public function store(Request $request, Campaign $campaign): JsonResponse
    {
        $this->authorizer->authorize(Actor::from($request->user()), 'create', Subscriber::class);

        $signup = $this->kit->signup($campaign)->email($request->string('email')->toString())->as($request->user());
        if ($request->filled('locale')) {
            $signup->locale($request->string('locale')->toString());
        }
        $result = $signup->subscribe();

        return new SubscriberResource($result->subscriber)->response()->setStatusCode($result->created ? 201 : 200);
    }

    public function destroy(Request $request, Campaign $campaign, Subscriber $subscriber): Response
    {
        $this->authorizer->authorize(Actor::from($request->user()), 'delete', $subscriber);
        $subscriber->delete();

        return new Response(status: 204);
    }

    public function convert(Request $request, Campaign $campaign, Subscriber $subscriber, ConvertSubscriber $convert): SubscriberResource
    {
        $actor = Actor::from($request->user());
        $this->authorizer->authorize($actor, 'convert', $subscriber);
        $convert->handle($subscriber, $actor);

        return new SubscriberResource($subscriber->refresh());
    }

    public function invite(InviteRequest $request, Campaign $campaign): JsonResponse
    {
        try {
            $count = $this->kit->invite($campaign)->top($request->integer('count'))->as($request->user())->send();
        } catch (NoInviteUrl $e) {
            return new JsonResponse(['message' => $e->getMessage(), 'errors' => ['count' => [$e->getMessage()]]], 422);
        }

        return new JsonResponse(['data' => ['invited' => $count]]);
    }

    public function export(Request $request, Campaign $campaign, SubscriberExport $export): StreamedResponse
    {
        $this->authorizer->authorize(Actor::from($request->user()), 'export', Subscriber::class);

        return new StreamedResponse(static function () use ($campaign, $export): void {
            $out = fopen('php://output', 'w');
            if ($out !== false) {
                $export->write($campaign, $out);
                fclose($out);
            }
        }, 200, [
            'Content-Type'        => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $campaign->slug . '-subscribers.csv"',
            'Cache-Control'       => 'no-store',
        ]);
    }

    /** Erases an address from every campaign: an erasure request. */
    public function forget(ForgetRequest $request, ForgetSubscriber $forget): JsonResponse
    {
        $actor = Actor::from($request->user());
        $this->authorizer->authorize($actor, 'erase', Subscriber::class);

        return new JsonResponse(['data' => $forget->handle($request->string('email')->toString(), $actor)]);
    }
}
