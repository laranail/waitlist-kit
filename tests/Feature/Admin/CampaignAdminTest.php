<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Simtabi\Laranail\WaitlistKit\Models\Block;
use Simtabi\Laranail\WaitlistKit\Auth\Abilities;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Themes\KitThemes;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignStatus;

dataset('modes', ['host', 'bundled']);

it('creates, edits and makes a campaign live', function (string $mode): void {
    signInAdmin($mode);

    $this->post('/waitlist-admin/campaigns', ['name' => 'Launch', 'slug' => 'launch', 'type' => 'waitlist', 'locales' => 'en, es'])
        ->assertRedirect();
    $campaign = Campaign::query()->sole();
    expect($campaign->locales)->toBe(['en', 'es']);

    $this->get("/waitlist-admin/campaigns/{$campaign->id}/edit")->assertOk()->assertSee('Launch');

    $this->put("/waitlist-admin/campaigns/{$campaign->id}", [
        'name' => 'Launch list', 'slug' => 'launch', 'type' => 'waitlist', 'status' => 'live', 'locales' => 'en', 'launches_at' => '2027-03-01T09:00',
    ])->assertRedirect();

    $campaign->refresh();
    expect($campaign->name)->toBe('Launch list')
        ->and($campaign->status)->toBe(CampaignStatus::Live)
        ->and($campaign->launches_at?->toDateString())->toBe('2027-03-01');
    $this->get('/waitlist/launch')->assertOk();
})->with('modes');

it('refuses a campaign that breaks the rules, and names every field', function (string $mode): void {
    signInAdmin($mode);

    $this->from('/waitlist-admin/campaigns/create')
        ->post('/waitlist-admin/campaigns', ['name' => '', 'slug' => 'Has Spaces', 'type' => 'party', 'locales' => 'english'])
        ->assertSessionHasErrors(['name', 'slug', 'type', 'locales.0']);
    expect(Campaign::query()->count())->toBe(0);
})->with('modes');

it('sets the theme and its settings, refusing an unsafe value', function (string $mode): void {
    signInAdmin($mode);
    $campaign = Campaign::factory()->create();

    $this->put("/waitlist-admin/campaigns/{$campaign->id}/theme", ['theme' => KitThemes::PLAIN, 'settings' => ['primary' => '#0f766e', 'show-powered-by' => '1', 'footer' => 'Hi']])
        ->assertRedirect();
    expect($campaign->fresh()?->theme)->toBe(KitThemes::PLAIN)
        ->and($campaign->fresh()?->theme_settings)->toMatchArray(['primary' => '#0f766e', 'show-powered-by' => '1', 'footer' => 'Hi']);

    $this->put("/waitlist-admin/campaigns/{$campaign->id}/theme", ['theme' => KitThemes::PLAIN, 'settings' => ['primary' => 'red;}body{x']])
        ->assertSessionHasErrors('settings.primary');
    $this->put("/waitlist-admin/campaigns/{$campaign->id}/theme", ['theme' => 'gone/theme'])->assertSessionHasErrors('theme');
})->with('modes');

it('adds, edits one language of, moves and removes blocks', function (string $mode): void {
    signInAdmin($mode);
    $campaign = Campaign::factory()->create(['locales' => ['en', 'es']]);

    $this->post("/waitlist-admin/campaigns/{$campaign->id}/blocks", ['type' => 'hero', 'locale' => 'en', 'title' => 'Hello'])->assertRedirect();
    $this->post("/waitlist-admin/campaigns/{$campaign->id}/blocks", ['type' => 'faq', 'locale' => 'en', 'items' => "When? | Soon\nWhere? | Here"])->assertRedirect();
    [$hero, $faq] = $campaign->blocks()->get()->all();

    expect($faq->content['en']['items'])->toBe([['question' => 'When?', 'answer' => 'Soon'], ['question' => 'Where?', 'answer' => 'Here']]);

    $this->put("/waitlist-admin/blocks/{$hero->id}", ['type' => 'hero', 'locale' => 'es', 'title' => 'Hola'])->assertRedirect();
    expect($hero->fresh()?->content)->toBe(['en' => ['title' => 'Hello'], 'es' => ['title' => 'Hola']]);

    $this->post("/waitlist-admin/blocks/{$faq->id}/move", ['direction' => 'up'])->assertRedirect();
    expect($campaign->blocks()->pluck('type')->all())->toBe(['faq', 'hero']);

    $this->delete("/waitlist-admin/blocks/{$hero->id}")->assertRedirect();
    expect(Block::query()->count())->toBe(1);
})->with('modes');

it('deletes a campaign only with erase and its slug typed back', function (string $mode): void {
    signInAdmin($mode);
    $campaign = Campaign::factory()->create(['slug' => 'launch']);
    Subscriber::factory()->count(2)->create(['campaign_id' => $campaign->id]);
    $campaign->blocks()->create(['type' => 'hero']);

    $this->delete("/waitlist-admin/campaigns/{$campaign->id}", ['confirm' => 'wrong'])->assertSessionHasErrors('confirm');
    expect(Campaign::query()->count())->toBe(1);

    $this->delete("/waitlist-admin/campaigns/{$campaign->id}", ['confirm' => 'launch'])->assertRedirect('/waitlist-admin/campaigns');
    expect(Campaign::query()->count())->toBe(0)
        ->and(Subscriber::withTrashed()->count())->toBe(0)
        ->and(Block::query()->count())->toBe(0);
})->with('modes');

it('lets someone with view but not manage look, never change', function (): void {
    signInAdmin('host');
    Gate::define(Abilities::MANAGE, fn (): bool => false);
    Gate::define(Abilities::ERASE, fn (): bool => false);
    $campaign = Campaign::factory()->create();

    $this->get('/waitlist-admin/campaigns')->assertOk()->assertDontSee('New campaign');
    $this->post('/waitlist-admin/campaigns', ['name' => 'X', 'slug' => 'x', 'type' => 'waitlist', 'locales' => 'en'])->assertForbidden();
    $this->post("/waitlist-admin/campaigns/{$campaign->id}/blocks", ['type' => 'hero', 'locale' => 'en'])->assertForbidden();
    $this->delete("/waitlist-admin/campaigns/{$campaign->id}", ['confirm' => $campaign->slug])->assertForbidden();
    $this->get("/waitlist-admin/campaigns/{$campaign->id}/edit")->assertOk()->assertDontSee('Delete this campaign');
});

it('stops offering a new campaign at the install\'s limit', function (): void {
    signInAdmin('host');
    config()->set('laranail.waitlist-kit.campaigns.mode', 'single');
    Campaign::factory()->create();

    $this->get('/waitlist-admin/campaigns')->assertDontSee('New campaign')->assertSee('This install runs one campaign.');
});
