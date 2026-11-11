<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Support\DateFactory;
use Simtabi\Laranail\WaitlistKit\Models\Enquiry;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Models\FunnelEvent;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;

/** The backend's front page: each campaign's list size and sign-ups this week, and the configuration's problems. */
final readonly class DashboardController
{
    public function __construct(private ViewFactory $views, private DateFactory $date, private KitConfig $config) {}

    public function show(): View
    {
        $since = $this->date->now()->subDays(7);

        return $this->views->make('laranail/waitlist-kit::admin.dashboard', [
            'campaigns' => Campaign::query()->withCount('subscribers')->orderBy('name')->get(),
            'week'      => FunnelEvent::query()->where('type', FunnelEventType::Signup)->where('occurred_at', '>=', $since)
                ->selectRaw('campaign_id, count(*) as total')->groupBy('campaign_id')->pluck('total', 'campaign_id'),
            'enquiries' => Enquiry::query()->where('created_at', '>=', $since)->count(),
            'problems'  => $this->config->problems(),
        ]);
    }
}
