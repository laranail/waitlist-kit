<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Simtabi\Laranail\WaitlistKit\Models\Page;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Enums\PageStatus;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignStatus;
use Simtabi\Laranail\WaitlistKit\Database\Seeders\WaitlistKitSeeder;

/*
 * The public pages' accessibility that markup can show: unique ids and live references in both themes, a countdown
 * that does not interrupt a screen reader, and forms whose errors reach assistive technology without JavaScript.
 * The in-place (JavaScript) path and a real screen reader are checked by hand: docs/accessibility.md.
 */

beforeEach(function (): void {
    Mail::fake();
    $this->seed(WaitlistKitSeeder::class);
    config()->set('laranail.waitlist-kit.contact.enabled', true);
    $campaign = Campaign::query()->sole();
    $campaign->update(['status' => CampaignStatus::Live, 'launches_at' => now()->addWeek()]);
    // The baseline has a hero, the sign-up form and an FAQ; the countdown and the contact form complete the page.
    $campaign->blocks()->create(['type' => 'countdown', 'content' => ['en' => ['label' => 'Opens in']]]);
    $campaign->blocks()->create(['type' => 'contact', 'content' => ['en' => ['title' => 'Questions?']]]);
    Page::query()->where('slug', Page::PRIVACY)->update(['status' => PageStatus::Published]);
});

it('keeps ids unique and references live on every public page, in both themes', function (string $theme): void {
    Campaign::query()->sole()->update(['theme' => $theme]);
    $pages = ['/waitlist/waitlist', '/waitlist/pages/privacy'];

    foreach ($pages as $url) {
        $html = (string) $this->get($url)->assertOk()->getContent();

        expect(referenceProblems($html))->toBe([], $url)
            ->and($html)->toMatch('/<html[^>]* lang="en"/')
            ->and(substr_count($html, '<h1'))->toBe(1);
    }
    expect($pages)->toHaveCount(2);
})->with(['laranail/waitlist-kit:default', 'laranail/waitlist-kit:plain']);

it('shows the countdown without a live region, with the date for assistive technology', function (): void {
    $html = (string) $this->get('/waitlist/waitlist')->assertOk()->getContent();

    expect($html)->toContain('data-block="countdown"')
        ->not->toContain('aria-live')
        ->toMatch('/<span aria-hidden="true" x-show="label" x-text="label"><\/span>\s*<time x-bind:class="dateClass" datetime="/');
});

it('lets the forms hand focus to their answer', function (): void {
    $html = (string) $this->get('/waitlist/waitlist')->assertOk()->getContent();

    expect(substr_count($html, 'data-part="status" role="status" tabindex="-1" hidden'))->toBe(2);
});

it('focuses a rejected sign-up field after a reload, with its error attached', function (): void {
    $html = (string) $this->followingRedirects()->from('/waitlist/waitlist')
        ->post('/waitlist/waitlist', ['email' => 'not-an-email'])->assertOk()->getContent();

    expect($html)->toMatch('/id="waitlist-email"[^>]*aria-invalid="true" aria-describedby="waitlist-email-error" autofocus/')
        ->and(referenceProblems($html))->toBe([]);
});

it('ties every contact form error to its control and focuses the first', function (): void {
    $html = (string) $this->followingRedirects()->from('/waitlist/waitlist')
        ->post('/waitlist/contact', ['name' => 'Dana Lee', 'email' => 'nope', 'topic' => 'sales', 'message' => 'short', 'return' => '/waitlist/waitlist'])
        ->assertOk()->getContent();

    expect($html)->toMatch('/id="contact-email"[^>]*aria-invalid="true" aria-describedby="contact-email-error"\s+autofocus/')
        ->toMatch('/id="contact-message"[^>]*aria-invalid="true" aria-describedby="contact-message-error"\s+>/')
        ->toMatch('/id="contact-agree"[^>]*aria-invalid="true" aria-describedby="contact-agree-error"/')
        ->and(substr_count($html, 'autofocus'))->toBe(1)
        ->and(referenceProblems($html))->toBe([])
        // The sign-up form on the same page also has `email`: it neither shows the error nor takes the input.
        ->and($html)->toMatch('/<input class="wk-input" data-part="input" id="waitlist-email" type="email" name="email" value="" required maxlength="254"\s+autocomplete="email" >/');
});
