<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Notifications;

/** A contact enquiry arrived. Its sender and message stay in the backend. */
final class EnquiryAlert extends Alert
{
    public function __construct(
        public readonly string $topic,
        public readonly ?string $backendUrl,
    ) {}

    public function subject(): string
    {
        return __('laranail/waitlist-kit::alerts.enquiry_subject', ['topic' => $this->topic]);
    }

    public function lines(): array
    {
        return [__('laranail/waitlist-kit::alerts.enquiry_line', ['topic' => $this->topic])];
    }

    public function url(): ?string
    {
        return $this->backendUrl;
    }
}
