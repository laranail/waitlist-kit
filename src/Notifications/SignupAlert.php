<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Notifications;

/** Someone new joined a campaign. */
final class SignupAlert extends Alert
{
    public function __construct(
        public readonly string $campaign,
        public readonly int $listSize,
        public readonly ?string $backendUrl,
    ) {}

    public function subject(): string
    {
        return __('laranail/waitlist-kit::alerts.signup_subject', ['campaign' => $this->campaign]);
    }

    public function lines(): array
    {
        return [__('laranail/waitlist-kit::alerts.signup_line', ['campaign' => $this->campaign, 'size' => $this->listSize])];
    }

    public function url(): ?string
    {
        return $this->backendUrl;
    }
}
