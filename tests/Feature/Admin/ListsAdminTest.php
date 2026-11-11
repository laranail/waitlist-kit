<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Simtabi\Laranail\WaitlistKit\Models\Page;
use Simtabi\Laranail\WaitlistKit\Auth\Abilities;
use Simtabi\Laranail\WaitlistKit\Models\Enquiry;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Enums\PageStatus;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Models\Integration;

dataset('modes', ['host', 'bundled']);

it('lists, searches and filters subscribers, and removes one', function (string $mode): void {
    signInAdmin($mode);
    $campaign = Campaign::factory()->create();
    $dana = Subscriber::factory()->create(['campaign_id' => $campaign->id, 'email' => 'dana@example.com']);
    Subscriber::factory()->confirmed()->create(['campaign_id' => $campaign->id, 'email' => 'kai@example.com']);

    $this->get("/waitlist-admin/campaigns/{$campaign->id}/subscribers")->assertOk()->assertSee('dana@example.com')->assertSee('kai@example.com');
    $this->get("/waitlist-admin/campaigns/{$campaign->id}/subscribers?q=DANA")->assertSee('dana@example.com')->assertDontSee('kai@example.com');
    $this->get("/waitlist-admin/campaigns/{$campaign->id}/subscribers?status=confirmed")->assertSee('kai@example.com')->assertDontSee('dana@example.com');
    $this->get("/waitlist-admin/campaigns/{$campaign->id}/subscribers?q=%25")->assertDontSee('dana@example.com');

    $this->delete("/waitlist-admin/campaigns/{$campaign->id}/subscribers/{$dana->id}")->assertRedirect();
    expect(Subscriber::query()->count())->toBe(1)->and(Subscriber::withTrashed()->count())->toBe(2);
})->with('modes');

it('exports a campaign\'s subscribers as CSV under the export ability only', function (string $mode): void {
    signInAdmin($mode);
    $campaign = Campaign::factory()->create(['slug' => 'launch']);
    Subscriber::factory()->create(['campaign_id' => $campaign->id, 'email' => 'dana@example.com']);

    $response = $this->get("/waitlist-admin/campaigns/{$campaign->id}/subscribers/export")->assertOk();
    expect($response->headers->get('Content-Disposition'))->toContain('launch-subscribers.csv')
        ->and($response->streamedContent())->toContain('dana@example.com');

    Gate::define(Abilities::EXPORT, fn (): bool => false);
    $this->get("/waitlist-admin/campaigns/{$campaign->id}/subscribers/export")->assertForbidden();
})->with('modes');

it('erases an address everywhere under the erase ability only', function (string $mode): void {
    signInAdmin($mode);
    Subscriber::factory()->create(['email' => 'dana@example.com']);
    Enquiry::factory()->create(['email' => 'dana@example.com']);

    $this->post('/waitlist-admin/forget', ['email' => 'dana@example.com'])->assertRedirect()->assertSessionHas('status', 'Erased 1 subscriber record(s) and 1 enquiry(ies).');
    expect(Subscriber::withTrashed()->count())->toBe(0)->and(Enquiry::query()->count())->toBe(0);

    Gate::define(Abilities::ERASE, fn (): bool => false);
    $this->post('/waitlist-admin/forget', ['email' => 'dana@example.com'])->assertForbidden();
})->with('modes');

it('lists enquiries and shows one in full', function (string $mode): void {
    signInAdmin($mode);
    $enquiry = Enquiry::factory()->create(['name' => 'Dana Lee', 'message' => "Line one\nLine two"]);

    $this->get('/waitlist-admin/enquiries')->assertOk()->assertSee('Dana Lee');
    $this->get("/waitlist-admin/enquiries/{$enquiry->id}")->assertOk()->assertSee("Line one\nLine two", false);
})->with('modes');

it('edits a page\'s title and blocks, and publishes it', function (string $mode): void {
    signInAdmin($mode);
    $page = Page::factory()->legal('privacy')->create(['title' => ['en' => 'Privacy']]);

    $this->post("/waitlist-admin/pages/{$page->id}/blocks", ['type' => 'text', 'locale' => 'en', 'heading' => 'What we keep', 'paragraphs' => "Your email.\n\nYour language."])->assertRedirect();
    expect($page->blocks()->sole()->content['en']['paragraphs'])->toBe(['Your email.', 'Your language.']);

    $this->put("/waitlist-admin/pages/{$page->id}", ['locale' => 'en', 'title' => 'Privacy notice', 'status' => 'published'])->assertRedirect();
    expect($page->fresh()?->status)->toBe(PageStatus::Published);
    $this->get('/waitlist/pages/privacy')->assertOk()->assertSee('Privacy notice')->assertSee('Your language.');
})->with('modes');

it('adds integrations, keeps a stored secret on edit and never shows it back', function (string $mode): void {
    signInAdmin($mode);
    $campaign = Campaign::factory()->create(['slug' => 'launch']);
    $key = bin2hex(random_bytes(16));

    $this->post('/waitlist-admin/integrations', ['driver' => 'app', 'endpoint' => 'http://plain.example.org/x', 'enabled' => '1'])->assertSessionHasErrors('endpoint');
    $this->post('/waitlist-admin/integrations', ['driver' => 'brevo', 'campaign' => 'launch', 'api_key' => $key, 'list_id' => 7, 'enabled' => '1'])->assertRedirect();

    $integration = Integration::query()->sole();
    expect($integration->settings)->toBe(['api_key' => $key, 'list_id' => 7])
        ->and($integration->integrable?->is($campaign))->toBeTrue();

    $this->get("/waitlist-admin/integrations/{$integration->id}/edit")->assertOk()->assertDontSee($key);
    $this->put("/waitlist-admin/integrations/{$integration->id}", ['driver' => 'brevo', 'campaign' => 'launch', 'api_key' => '', 'list_id' => 9, 'enabled' => '1'])->assertRedirect();
    expect($integration->fresh()?->settings)->toBe(['api_key' => $key, 'list_id' => 9]);
})->with('modes');

it('shows the dashboard with each campaign and the configuration\'s problems', function (string $mode): void {
    signInAdmin($mode);
    $campaign = Campaign::factory()->create(['name' => 'Launch']);
    Subscriber::factory()->count(3)->create(['campaign_id' => $campaign->id]);

    $this->get('/waitlist-admin')->assertOk()->assertSee('Launch')->assertSee('>3<', false);

    config()->set('laranail.waitlist-kit.campaigns.mode', 'limited');
    config()->set('laranail.waitlist-kit.campaigns.max', 0);
    $this->get('/waitlist-admin')->assertSee('campaigns.max must be at least 1');
})->with('modes');
