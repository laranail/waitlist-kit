<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Support;

use DateTimeInterface;
use Illuminate\Support\DateFactory;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Data\FunnelSummary;
use Simtabi\Laranail\WaitlistKit\Models\FunnelEvent;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;

/**
 * Reads a campaign's funnel from its events: one grouped count on the funnel index for the totals, then the window's
 * sign-up times and attribution grouped here (by day, by source), which keeps the SQL the same on SQLite, MySQL and
 * PostgreSQL. Counts are of events, so someone who signed up and later left still counts as a sign-up.
 */
final readonly class FunnelReport
{
    public const int DEFAULT_DAYS = 30;

    public const int TOP_SOURCES = 5;

    public function __construct(private DateFactory $date) {}

    /**
     * Where a sign-up came from: its utm_source, else the referring site's host, else `direct`.
     *
     * @param array<array-key, mixed> $attribution
     */
    public static function source(array $attribution): string
    {
        $utm = $attribution['utm_source'] ?? null;
        if (is_string($utm) && $utm !== '') {
            return mb_strtolower($utm);
        }

        $referrer = $attribution['referrer'] ?? null;
        $host = is_string($referrer) ? parse_url(str_contains($referrer, '://') ? $referrer : 'https://' . $referrer, PHP_URL_HOST) : null;

        return is_string($host) && $host !== '' ? mb_strtolower($host) : 'direct';
    }

    public function for(Campaign $campaign, int $days = self::DEFAULT_DAYS): FunnelSummary
    {
        // Immutable: the factory may hand out mutable dates, and the day loop below must not move $today.
        $today = $this->date->now()->toImmutable()->startOfDay();
        $since = $today->subDays($days - 1);

        $counts = array_fill_keys(array_map(static fn (FunnelEventType $t): string => $t->value, FunnelEventType::cases()), 0);
        $totals = FunnelEvent::query()
            ->where('campaign_id', $campaign->id)
            ->where('occurred_at', '>=', $since)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');
        foreach ($totals as $type => $total) {
            if (is_string($type) && array_key_exists($type, $counts) && is_numeric($total)) {
                $counts[$type] = (int) $total;
            }
        }

        $byDay = [];
        for ($day = $since; $day <= $today; $day = $day->addDay()) {
            $byDay[$day->toDateString()] = 0;
        }
        $signups = FunnelEvent::query()
            ->where('campaign_id', $campaign->id)
            ->where('type', FunnelEventType::Signup)
            ->where('occurred_at', '>=', $since)
            ->pluck('occurred_at');
        foreach ($signups as $at) {
            if (! is_string($at) && ! $at instanceof DateTimeInterface) {
                continue;
            }
            $key = $this->date->parse($at instanceof DateTimeInterface ? $at->format('Y-m-d H:i:s') : $at)->toDateString();
            if (array_key_exists($key, $byDay)) {
                $byDay[$key]++;
            }
        }

        $sources = [];
        $attributions = Subscriber::query()->withTrashed()
            ->where('campaign_id', $campaign->id)
            ->where('created_at', '>=', $since)
            ->pluck('attribution');
        foreach ($attributions as $attribution) {
            $source = self::source(is_array($attribution) ? $attribution : []);
            $sources[$source] = ($sources[$source] ?? 0) + 1;
        }
        arsort($sources);

        return new FunnelSummary($counts, $byDay, array_slice($sources, 0, self::TOP_SOURCES, true), $days);
    }
}
