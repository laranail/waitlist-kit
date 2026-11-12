<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers\Api\Manage;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Simtabi\Laranail\WaitlistKit\WaitlistKit;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Services\Authorizer;
use Simtabi\Laranail\WaitlistKit\Support\FunnelReport;
use Simtabi\Laranail\WaitlistKit\Builders\CampaignBuilder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Simtabi\Laranail\WaitlistKit\Http\Requests\Api\ListRequest;
use Simtabi\Laranail\WaitlistKit\Http\Resources\FunnelResource;
use Simtabi\Laranail\WaitlistKit\Http\Resources\CampaignResource;

/**
 * `manage/campaigns`: list, read, create, change and delete campaigns, and read a funnel. Every write goes through
 * the CampaignBuilder as the signed-in user, so it meets the same rules, limits and policies as the backend and code.
 */
final readonly class CampaignController
{
    public function __construct(private WaitlistKit $kit, private Authorizer $authorizer) {}

    public function index(ListRequest $request): AnonymousResourceCollection
    {
        $this->authorizer->authorize(Actor::from($request->user()), 'viewAny', Campaign::class);

        return CampaignResource::collection(Campaign::query()->orderByDesc('created_at')->orderByDesc('id')->paginate($request->perPage()));
    }

    public function show(Request $request, Campaign $campaign): CampaignResource
    {
        $this->authorizer->authorize(Actor::from($request->user()), 'view', $campaign);

        return new CampaignResource($campaign);
    }

    public function store(Request $request): JsonResponse
    {
        $campaign = $this->fill($this->kit->campaign(), $request)->as($request->user())->create();

        return new CampaignResource($campaign)->response()->setStatusCode(201);
    }

    /** PUT or PATCH: only the fields sent change. */
    public function update(Request $request, Campaign $campaign): CampaignResource
    {
        return new CampaignResource($this->fill($this->kit->campaign($campaign), $request)->as($request->user())->save());
    }

    public function destroy(Request $request, Campaign $campaign): Response
    {
        $this->kit->campaign($campaign)->as($request->user())->delete();

        return new Response(status: 204);
    }

    public function funnel(ListRequest $request, Campaign $campaign, FunnelReport $report): FunnelResource
    {
        $this->authorizer->authorize(Actor::from($request->user()), 'view', $campaign);

        return new FunnelResource($report->for($campaign, $request->filled('days') ? $request->integer('days') : FunnelReport::DEFAULT_DAYS));
    }

    /** The fields the request sent, onto the builder; the builder validates them. */
    private function fill(CampaignBuilder $builder, Request $request): CampaignBuilder
    {
        $text = static fn (string $key): ?string => $request->has($key) && is_scalar($request->input($key)) ? (string) $request->input($key) : null;

        if (($name = $text('name')) !== null) {
            $builder->named($name);
        }
        if (($slug = $text('slug')) !== null) {
            $builder->slug($slug);
        }
        if (($type = $text('type')) !== null) {
            $builder->type($type);
        }
        if (($status = $text('status')) !== null) {
            $builder->status($status);
        }
        if ($request->has('locales')) {
            $locales = $request->input('locales');
            $list = is_array($locales) ? $locales : explode(',', is_string($locales) ? $locales : '');
            $builder->locales(...array_values(array_map(static fn (mixed $locale): string => trim(is_scalar($locale) ? (string) $locale : ''), $list)));
        }
        if ($request->has('host')) {
            $builder->host($text('host'));
        }
        if ($request->has('launches_at')) {
            $builder->launchesAt($text('launches_at'));
        }

        return $builder;
    }
}
