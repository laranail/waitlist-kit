<?php

declare(strict_types=1);

use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignStatus;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;
use Simtabi\Laranail\WaitlistKit\Providers\WaitlistKitServiceProvider;

/** A live campaign at /waitlist/launch with the given type, settings and languages. */
function pageCampaign(CampaignType $type = CampaignType::Waitlist, array $settings = [], array $locales = ['en']): Campaign
{
    return Campaign::factory()->live()->create([
        'slug'     => 'launch', 'name' => 'Northwind', 'type' => $type, 'locales' => $locales,
        'settings' => $settings, 'launches_at' => now()->addWeek(),
    ]);
}

it('renders every campaign type in both themes', function (CampaignType $type, string $theme): void {
    pageCampaign($type, ['conversion' => ['invite_url' => 'https://app.example.com/join']])->update(['theme' => $theme]);

    $html = (string) $this->get('/waitlist/launch')->assertOk()->getContent();

    expect(referenceProblems($html))->toBe([])
        ->and($html)->toContain('data-theme="' . $theme . '"')->toContain('data-scope="signup-form"');
    match ($type) {
        CampaignType::ComingSoon  => expect($html)->toContain('data-block="countdown"')->toContain('>Notify me</button>'),
        CampaignType::EarlyAccess => expect($html)->toContain('data-block="access"')->toContain('action="' . route('laranail-waitlist-kit.campaign.access', 'launch') . '"'),
        CampaignType::Waitlist    => expect($html)->not->toContain('data-block="access"')->toContain('>Join the waitlist</button>'),
    };
})->with(CampaignType::cases())->with(['laranail/waitlist-kit:default', 'laranail/waitlist-kit:plain']);

it('answers by status: draft 404, closed and launched notices, a launch redirect to https only', function (): void {
    $campaign = pageCampaign();

    $campaign->update(['status' => CampaignStatus::Draft]);
    $this->get('/waitlist/launch')->assertNotFound();

    $campaign->update(['status' => CampaignStatus::Closed]);
    $this->get('/waitlist/launch')->assertOk()->assertSee('Sign-ups have closed')->assertDontSee('data-scope="signup-form"', false);
    $this->postJson('/waitlist/launch', ['email' => 'ada@example.com'])->assertNotFound();

    $campaign->update(['status' => CampaignStatus::Launched, 'settings' => ['pages' => ['launch_url' => 'http://insecure.example.com']]]);
    $this->get('/waitlist/launch')->assertOk()->assertSee('Northwind has launched');

    $campaign->update(['settings' => ['pages' => ['launch_url' => 'https://app.example.com/']]]);
    $this->get('/waitlist/launch')->assertRedirect('https://app.example.com/');
});

it('is noindex unless live with indexing on', function (CampaignStatus $status, bool $index, string $robots): void {
    pageCampaign(settings: ['seo' => ['index' => $index]])->update(['status' => $status]);

    $this->get('/waitlist/launch')->assertOk()->assertSee('<meta name="robots" content="' . $robots . '">', false);
})->with([
    'live, indexing off'  => [CampaignStatus::Live, false, 'noindex'],
    'live, indexing on'   => [CampaignStatus::Live, true, 'index, follow'],
    'closed, indexing on' => [CampaignStatus::Closed, true, 'noindex'],
]);

it('writes title, description, canonical, share image and language alternates, and honours ?lang', function (): void {
    pageCampaign(settings: ['seo' => [
        'index' => true, 'image' => 'https://cdn.example.com/og.png',
        'title' => ['en' => 'Northwind early list', 'es' => 'Lista de Northwind'], 'description' => ['en' => 'Be first in.'],
    ]], locales: ['en', 'es']);

    $this->get('/waitlist/launch')->assertOk()
        ->assertSee('<title>Northwind early list</title>', false)
        ->assertSee('<meta name="description" content="Be first in.">', false)
        ->assertSee('<link rel="canonical" href="' . url('/waitlist/launch') . '">', false)
        ->assertSee('<link rel="alternate" hreflang="es" href="' . url('/waitlist/launch?lang=es') . '">', false)
        ->assertSee('<link rel="alternate" hreflang="x-default" href="' . url('/waitlist/launch') . '">', false)
        ->assertSee('<meta property="og:image" content="https://cdn.example.com/og.png">', false);

    $this->get('/waitlist/launch?lang=es')->assertOk()
        ->assertSee('<html lang="es"', false)
        ->assertSee('<title>Lista de Northwind</title>', false)
        ->assertSee('<meta name="description" content="Be first in.">', false)
        ->assertSee('<link rel="canonical" href="' . url('/waitlist/launch?lang=es') . '">', false);

    $this->get('/waitlist/launch?lang=xx')->assertOk()->assertSee('<html lang="en"', false);
});

it('lists only indexable live campaigns in the sitemap, with their languages', function (): void {
    Campaign::factory()->live()->create(['slug' => 'open', 'locales' => ['en', 'fr'], 'settings' => ['seo' => ['index' => true]]]);
    Campaign::factory()->live()->create(['slug' => 'quiet', 'locales' => ['en']]);
    Campaign::factory()->create(['slug' => 'later', 'locales' => ['en'], 'settings' => ['seo' => ['index' => true]]]);

    $xml = (string) $this->get('/waitlist/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();

    expect($xml)->toContain('<loc>' . url('/waitlist/open') . '</loc>')
        ->toContain('<loc>' . url('/waitlist/open?lang=fr') . '</loc>')
        ->toContain('hreflang="fr" href="' . url('/waitlist/open?lang=fr') . '"')
        ->not->toContain('/waitlist/quiet')->not->toContain('/waitlist/later')
        ->and(simplexml_load_string($xml))->not->toBeFalse();
});

it('sends an invited code to the invite URL, and refuses every other code the same way', function (): void {
    $campaign = pageCampaign(CampaignType::EarlyAccess, ['conversion' => ['invite_url' => 'https://app.example.com/join']]);
    $invited = Subscriber::factory()->create(['campaign_id' => $campaign->id, 'status' => SubscriberStatus::Invited, 'referral_code' => 'k3m9p2x7qa']);
    Subscriber::factory()->create(['campaign_id' => $campaign->id, 'status' => SubscriberStatus::Confirmed, 'referral_code' => 'pending123']);

    $this->post('/waitlist/launch/access', ['code' => ' K3M9P2X7QA '])->assertRedirect('https://app.example.com/join?invite=k3m9p2x7qa');

    foreach (['pending123', 'unknown999', '<b>'] as $code) {
        $this->post('/waitlist/launch/access', ['code' => $code])
            ->assertRedirect('/waitlist/launch')
            ->assertSessionHasErrors(['code' => "That code isn't valid for this list."], null, 'access');
    }

    $campaign->update(['type' => CampaignType::Waitlist]);
    $this->post('/waitlist/launch/access', ['code' => $invited->referral_code])->assertNotFound();
});

it('throttles invitation code attempts', function (): void {
    pageCampaign(CampaignType::EarlyAccess, ['conversion' => ['invite_url' => 'https://app.example.com/join']]);

    foreach (range(1, WaitlistKitServiceProvider::ACCESS_PER_MINUTE) as $i) {
        $this->post('/waitlist/launch/access', ['code' => 'wrong' . $i . 'xx'])->assertRedirect();
    }
    $this->post('/waitlist/launch/access', ['code' => 'onemorexx'])->assertTooManyRequests();
});

it('saves page and search settings per language from the campaign screen', function (): void {
    $campaign = pageCampaign(settings: ['keep' => 'me', 'seo' => ['title' => ['en' => 'English title']]], locales: ['en', 'es']);
    signInAdmin('host');

    $this->put("/waitlist-admin/campaigns/{$campaign->id}/seo", ['locale' => 'es', 'title' => 'Título', 'description' => 'Descripción', 'image' => 'https://cdn.example.com/og.png', 'index' => '1', 'launch_url' => 'https://app.example.com/'])
        ->assertRedirect();
    $this->put("/waitlist-admin/campaigns/{$campaign->id}/seo", ['locale' => 'es', 'index' => '1', 'image' => 'http://insecure.example.com/og.png'])
        ->assertSessionHasErrors('image');

    $campaign->refresh();
    expect($campaign->settings['keep'])->toBe('me')
        ->and($campaign->seo()->titles)->toBe(['en' => 'English title', 'es' => 'Título'])
        ->and($campaign->seo()->index)->toBeTrue()
        ->and($campaign->launchUrl())->toBe('https://app.example.com/');
    $this->get("/waitlist-admin/campaigns/{$campaign->id}/edit?locale=es")->assertOk()->assertSee('value="Título"', false);
});
