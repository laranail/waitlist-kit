<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Builders;

use Simtabi\Laranail\WaitlistKit\Models\Enquiry;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Data\ContactData;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Actions\SubmitEnquiry;
use Simtabi\Laranail\WaitlistKit\Support\CampaignLookup;
use Simtabi\Laranail\WaitlistKit\Validation\ContactRules;
use Simtabi\Laranail\WaitlistKit\Builders\Concerns\ActsAs;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

/**
 * An enquiry from code, held to the contact form's rules (ContactRules):
 *
 *     $kit->enquiry()->from('Dana Lee', 'dana@example.com')->topic('sales')->message('...')->consented()->submit();
 */
final class EnquiryBuilder
{
    use ActsAs;

    private string $name = '';

    private string $email = '';

    private ?string $company = null;

    private string $topic = '';

    private string $message = '';

    private string $locale = 'en';

    private bool $consented = false;

    private ?Campaign $campaign = null;

    public function __construct(
        private readonly SubmitEnquiry $submit,
        private readonly ValidationFactory $validator,
        private readonly KitConfig $config,
    ) {}

    public function from(string $name, string $email): self
    {
        $this->name = trim($name);
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function company(?string $company): self
    {
        $this->company = $company === null || trim($company) === '' ? null : trim($company);

        return $this;
    }

    public function topic(string $topic): self
    {
        $this->topic = $topic;

        return $this;
    }

    public function message(string $message): self
    {
        $this->message = trim($message);

        return $this;
    }

    public function locale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    /** The live campaign the enquiry is about. */
    public function about(Campaign|string $campaign): self
    {
        $this->campaign = CampaignLookup::resolve($campaign);

        return $this;
    }

    /** They agreed to the privacy notice (required when contact.consent is on). */
    public function consented(bool $consented = true): self
    {
        $this->consented = $consented;

        return $this;
    }

    public function submit(): Enquiry
    {
        $this->validator->make(array_filter([
            'name'     => $this->name,
            'email'    => $this->email,
            'company'  => $this->company,
            'topic'    => $this->topic,
            'message'  => $this->message,
            'locale'   => $this->locale,
            'campaign' => $this->campaign?->slug,
            'agree'    => $this->consented ? '1' : null,
        ], static fn (?string $value): bool => $value !== null), ContactRules::rules($this->config))->validate();

        return $this->submit->handle(new ContactData(
            name: $this->name,
            email: $this->email,
            topic: $this->topic,
            message: $this->message,
            locale: $this->locale,
            company: $this->company,
            campaignId: $this->campaign?->id,
            consented: $this->consented,
        ), $this->actor());
    }
}
