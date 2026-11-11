<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Http\Requests;

use Override;
use Illuminate\Foundation\Http\FormRequest;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Data\ContactData;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Support\ReturnPath;
use Simtabi\Laranail\WaitlistKit\Validation\ContactRules;

/**
 * An enquiry from the contact form, on any page. Without JavaScript the visitor returns to `return`, which must be a
 * path on this site (never `//host` or a full URL), else to the home page.
 */
final class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(KitConfig $config): array
    {
        $rules = ContactRules::rules($config);

        if ($config->requiresCaptcha()) {
            $rules['captcha'] = ['required', 'laranail_captcha'];
        }

        return $rules;
    }

    public function toData(): ContactData
    {
        $campaign = $this->filled('campaign')
            ? Campaign::query()->where('slug', $this->string('campaign')->toString())->first()
            : null;

        $locale = $this->string('locale')->toString();

        return new ContactData(
            name: trim($this->string('name')->toString()),
            email: mb_strtolower(trim($this->string('email')->toString())),
            topic: $this->string('topic')->toString(),
            message: trim($this->string('message')->toString()),
            locale: $locale !== '' ? $locale : 'en',
            company: $this->filled('company') ? trim($this->string('company')->toString()) : null,
            campaignId: $campaign?->id,
            consented: $this->boolean('agree'),
        );
    }

    /** The page to send a visitor back to: a path on this site, or the home page. */
    public function returnPath(): string
    {
        return ReturnPath::from($this->string('return')->toString());
    }

    #[Override]
    protected function getRedirectUrl(): string
    {
        return $this->redirector->getUrlGenerator()->to($this->returnPath());
    }
}
