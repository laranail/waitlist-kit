<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Data;

use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;

/**
 * One campaign's funnel over a window: how many of each event, the rates between the steps, sign-ups per day, and the
 * top sources of sign-ups. Built by FunnelReport from the events table, so it counts what happened, not who is left.
 */
final readonly class FunnelSummary
{
    /**
     * @param array<string, int> $counts every FunnelEventType value, zero when none
     * @param array<string, int> $byDay Y-m-d => sign-ups, every day of the window, oldest first
     * @param array<string, int> $sources source => sign-ups, largest first
     */
    public function __construct(
        public array $counts,
        public array $byDay,
        public array $sources,
        public int $days,
    ) {}

    public function count(FunnelEventType $type): int
    {
        return $this->counts[$type->value] ?? 0;
    }

    /** $to as a percentage of $from, to one decimal place; null when nothing reached $from. */
    public function rate(FunnelEventType $from, FunnelEventType $to): ?float
    {
        $base = $this->count($from);

        return $base === 0 ? null : round($this->count($to) / $base * 100, 1);
    }
}
