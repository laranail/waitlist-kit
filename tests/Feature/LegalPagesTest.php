<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Simtabi\Laranail\WaitlistKit\Models\Page;
use Illuminate\Validation\ValidationException;
use Simtabi\Laranail\WaitlistKit\Enums\PageKind;
use Simtabi\Laranail\WaitlistKit\Enums\PageStatus;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;
use Simtabi\Laranail\WaitlistKit\Actions\CreateCampaign;
use Simtabi\Laranail\WaitlistKit\Database\Seeders\WaitlistKitSeeder;

it('seeds privacy and terms as drafts, which are not served', function (): void {
    $this->seed(WaitlistKitSeeder::class);

    expect(Page::query()->where('kind', PageKind::Legal)->pluck('slug')->sort()->values()->all())->toBe(['privacy', 'terms'])
        ->and(Page::query()->where('status', PageStatus::Published)->count())->toBe(0);

    $this->get('/waitlist/pages/privacy')->assertNotFound();
});

it('describes what the kit stores, and marks every site fact for the owner to fill', function (): void {
    $this->seed(WaitlistKitSeeder::class);
    $text = Page::query()->where('slug', 'privacy')->sole()->blocks()->get()
        ->map(fn ($block): string => implode(' ', (array) ($block->contentFor('en')['paragraphs'] ?? [])))->implode(' ');

    expect($text)->toContain('your email address')
        ->toContain('referral code')
        ->toContain('[Your organisation')
        ->toContain('publish it');
});

it('does not overwrite a page that exists', function (): void {
    Page::factory()->legal('privacy')->published()->create(['title' => ['en' => 'Our privacy notice']]);

    $this->seed(WaitlistKitSeeder::class);

    expect(Page::query()->where('slug', 'privacy')->sole()->title)->toBe(['en' => 'Our privacy notice']);
});

it('serves a published page with its blocks', function (): void {
    $page = Page::factory()->legal('privacy')->published()->create(['title' => ['en' => 'Privacy notice']]);
    $page->blocks()->create(['type' => 'text', 'content' => ['en' => ['heading' => 'What we keep', 'paragraphs' => ['Your email address.']]]]);

    $this->get('/waitlist/pages/privacy')->assertOk()
        ->assertSee('Privacy notice')->assertSee('What we keep')->assertSee('Your email address.');
});

it('lists only published legal pages in the links component', function (): void {
    Page::factory()->legal('privacy')->published()->create(['title' => ['en' => 'Privacy notice']]);
    Page::factory()->legal('terms')->create(['title' => ['en' => 'Terms of use']]);

    $html = Blade::render('<x-laranail-waitlist-kit::legal-links />');

    expect($html)->toContain('Privacy notice')->not->toContain('Terms of use')->toContain('data-scope="legal-links"');
});

it('links the sign-up form to the privacy notice once published', function (): void {
    liveCampaign();
    $this->get('/waitlist/launch')->assertDontSee('data-part="consent"', false);

    Page::factory()->legal('privacy')->published()->create();
    $this->get('/waitlist/launch')->assertSee('data-part="consent"', false);
});

it('reserves the kit\'s own paths as campaign slugs', function (): void {
    expect(fn () => app(CreateCampaign::class)
        ->handle('Contact', 'contact', CampaignType::Waitlist))
        ->toThrow(ValidationException::class);
});
