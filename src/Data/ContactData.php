<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Data;

/** A validated enquiry: what ContactRequest hands SubmitEnquiry. */
final readonly class ContactData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $topic,
        public string $message,
        public string $locale,
        public ?string $company = null,
        public ?string $campaignId = null,
        public bool $consented = false,
    ) {}
}
