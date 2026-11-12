<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions\Admin;

use Illuminate\Contracts\Events\Dispatcher;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Events\CampaignUpdated;

/** Saves a campaign's own fields, validated by UpdateCampaignRequest (CampaignRules::update). */
final readonly class UpdateCampaign
{
    public function __construct(private Dispatcher $events) {}

    /** @param  array{name: string, slug: string, type: string, status: string, locales: list<string>, host?: ?string, launches_at?: ?string}  $data */
    public function handle(Campaign $campaign, array $data, ?Actor $actor = null): Campaign
    {
        $campaign->update([
            'name'        => $data['name'],
            'slug'        => $data['slug'],
            'type'        => $data['type'],
            'status'      => $data['status'],
            'locales'     => array_values($data['locales']),
            'host'        => ($data['host'] ?? null) ?: null,
            'launches_at' => ($data['launches_at'] ?? null) ?: null,
        ]);

        $changes = $campaign->getChanges();
        if ($changes !== []) {
            $this->events->dispatch(new CampaignUpdated($campaign, $changes, $actor ?? Actor::system()));
        }

        return $campaign;
    }
}
