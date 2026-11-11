<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\View\Components;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\ViewErrorBag;
use Simtabi\Laranail\WaitlistKit\Models\Page;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Illuminate\Contracts\View\Factory as ViewFactory;

/**
 * `<x-laranail-waitlist-kit::contact-form />`: the headless contact form. Accessible markup with no classes and no
 * styles, only a `data-scope`/`data-part`/`data-state` contract for any CSS to target, so it fits any page and any
 * theme. Attributes passed to the tag (a `class`, an `id`) land on the form.
 */
final class ContactForm extends Component
{
    public function __construct(
        private readonly KitConfig $config,
        private readonly ViewFactory $views,
        public ?string $campaign = null,
        public ?string $return = null,
    ) {}

    public function shouldRender(): bool
    {
        return $this->config->contactEnabled();
    }

    public function render(): View
    {
        $privacy = Page::query()->where('slug', Page::PRIVACY)->first();

        return view('laranail/waitlist-kit::components.contact-form', [
            'topics'   => $this->config->contactTopics(),
            'company'  => $this->config->contactCompany(),
            'consent'  => $this->config->contactRequiresConsent(),
            'privacy'  => $privacy?->isPublished() === true ? route('laranail-waitlist-kit.page.show', $privacy) : null,
            'honeypot' => $this->config->honeypotField(),
            'captcha'  => $this->config->requiresCaptcha(),
            // Outside the web middleware (a console render, a mail) nothing shares an error bag; @error needs one.
            'errors' => $this->views->shared('errors', new ViewErrorBag),
        ]);
    }
}
