<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Notifications;

/** A campaign's list reached one of the configured sizes. */
final class MilestoneAlert extends Alert
{
    public function __construct(
        public readonly string $campaign,
        public readonly int $size,
        public readonly ?string $backendUrl,
    ) {}

    public function subject(): string
    {
        return __('laranail/waitlist-kit::alerts.milestone_subject', ['campaign' => $this->campaign, 'size' => number_format($this->size)]);
    }

    public function lines(): array
    {
        return [__('laranail/waitlist-kit::alerts.milestone_line', ['campaign' => $this->campaign, 'size' => number_format($this->size)])];
    }

    public function url(): ?string
    {
        return $this->backendUrl;
    }
}
