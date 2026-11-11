<?php

declare(strict_types=1);

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\View;
use Illuminate\Filesystem\Filesystem;
use Simtabi\Laranail\WaitlistKit\Models\Block;
use Simtabi\Laranail\WaitlistKit\Themes\Theme;
use Simtabi\Laranail\WaitlistKit\Enums\BlockType;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Themes\KitThemes;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;
use Simtabi\Laranail\WaitlistKit\Enums\ThemeStyling;
use Simtabi\Laranail\WaitlistKit\Themes\ThemeSetting;
use Simtabi\Laranail\WaitlistKit\Themes\ThemeRegistry;

/** A live campaign of the given type with one block of every type, in the given theme. */
function everyBlock(string $theme, CampaignType $type, array $settings = []): Campaign
{
    $campaign = Campaign::factory()->live()->create([
        'slug' => 'launch', 'type' => $type, 'theme' => $theme, 'theme_settings' => $settings, 'launches_at' => now()->addDays(3),
    ]);
    $content = [
        'hero'         => ['title' => 'Something new', 'body' => 'Be first.'],
        'form'         => ['label' => 'Your email', 'button' => 'Join'],
        'features'     => ['title' => 'Why', 'items' => [['title' => 'Fast', 'body' => 'Very.']]],
        'countdown'    => ['label' => 'Opens in'],
        'faq'          => ['items' => [['question' => 'When?', 'answer' => 'Soon.']]],
        'social_proof' => ['quote' => 'Cannot wait.', 'author' => 'Dana'],
        'contact'      => ['title' => 'Talk to us'],
        'text'         => ['heading' => 'More', 'paragraphs' => ['Details.']],
        'footer'       => ['text' => 'Made with care.'],
    ];
    foreach (BlockType::cases() as $position => $block) {
        $campaign->blocks()->create(['type' => $block->value, 'position' => $position, 'content' => ['en' => $content[$block->value]]]);
    }

    return $campaign;
}

it('renders every block in both kit themes for every campaign type', function (string $theme, CampaignType $type): void {
    everyBlock($theme, $type);

    $html = $this->get('/waitlist/launch')->assertOk()->getContent();

    foreach (BlockType::cases() as $block) {
        expect($html)->toContain('data-block="' . $block->value . '"');
    }
    expect($html)->toContain('data-theme="' . $theme . '"');
    expect(BlockType::cases())->toHaveCount(9);
})->with([KitThemes::DEFAULT, KitThemes::PLAIN])->with(CampaignType::cases());

it('links each theme\'s own prebuilt stylesheet and the kit script, with no Node at runtime', function (): void {
    $manifest = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/resources/dist/manifest.json'), true);

    everyBlock(KitThemes::PLAIN, CampaignType::Waitlist);
    $plain = $this->get('/waitlist/launch')->getContent();
    expect($plain)->toContain($manifest['resources/assets/css/themes/plain.css']['file'])
        ->toContain($manifest['resources/assets/js/kit.js']['file'])
        ->not->toContain($manifest['resources/assets/css/themes/default.css']['file']);

    Campaign::query()->sole()->update(['theme' => KitThemes::DEFAULT]);
    expect($this->get('/waitlist/launch')->getContent())->toContain($manifest['resources/assets/css/themes/default.css']['file']);
});

it('turns the campaign\'s settings into --wk-* properties, refusing anything unsafe', function (): void {
    everyBlock(KitThemes::DEFAULT, CampaignType::Waitlist, [
        'primary'    => '#0f766e',
        'background' => 'red;}body{display:none',
        'font'       => 'Inter; } @import url(x)',
        'logo'       => 'javascript:alert(1)',
        'footer'     => 'Built in Nairobi',
    ]);
    // The block's own text wins over the theme's footer setting, so clear it to see the setting.
    Block::query()->where('type', 'footer')->update(['content' => ['en' => []]]);

    $html = $this->get('/waitlist/launch')->getContent();

    expect($html)->toContain('--wk-primary: #0f766e;')
        ->toContain('--wk-background: #ffffff;')
        ->toContain('--wk-font: system-ui, sans-serif;')
        ->toContain('--wk-logo: none;')
        ->not->toContain('display:none')
        ->not->toContain('@import')
        ->not->toContain('javascript:')
        ->toContain('Built in Nairobi');
});

it('accepts an https or site-relative logo, and nothing that could close the url()', function (): void {
    $logo = ThemeSetting::image('logo');

    expect($logo->cssValue('https://cdn.example.org/logo.svg'))->toBe('url("https://cdn.example.org/logo.svg")')
        ->and($logo->cssValue('/images/logo.png'))->toBe('url("/images/logo.png")')
        ->and($logo->cssValue('//evil.example/x.png'))->toBe('none')
        ->and($logo->cssValue('https://x.example/a")b'))->toBe('none')
        ->and($logo->cssValue('http://plain.example/x.png'))->toBe('none');
});

it('falls back to the base view for anything a theme does not override', function (): void {
    View::addNamespace('acme-theme', __DIR__ . '/../Fixtures/theme');
    app(ThemeRegistry::class)->register(new Theme(
        name: 'acme/waitlist-theme',
        label: 'Acme',
        styling: ThemeStyling::Css,
        buildDirectory: KitThemes::BUILD_DIRECTORY,
        entries: ['resources/assets/css/themes/plain.css'],
        views: 'acme-theme::',
    ));
    everyBlock('acme/waitlist-theme', CampaignType::Waitlist);

    $html = $this->get('/waitlist/launch')->assertOk()->getContent();

    expect($html)->toContain('(Acme hero)')
        ->toContain('data-fixture-theme="acme"')
        ->toContain('data-block="faq"')
        ->toContain('class="wk-faq-item"');
});

it('lets a theme package register itself from its provider', function (): void {
    app()->afterResolving(ThemeRegistry::class, fn (ThemeRegistry $themes) => $themes->register(new Theme(
        name: 'acme/waitlist-theme',
        label: 'Acme',
        styling: ThemeStyling::Tailwind,
        buildDirectory: 'vendor/acme-theme',
        entries: ['resources/css/acme.css'],
    )));
    app()->forgetInstance(ThemeRegistry::class);

    expect(app(ThemeRegistry::class)->has('acme/waitlist-theme'))->toBeTrue()
        ->and(app(ThemeRegistry::class)->has(KitThemes::DEFAULT))->toBeTrue();
});

it('renders a campaign whose theme was uninstalled in the default theme', function (): void {
    everyBlock('gone/theme', CampaignType::Waitlist);

    $this->get('/waitlist/launch')->assertOk()->assertSee('data-theme="' . KitThemes::DEFAULT . '"', false);
    $this->artisan('laranail::waitlist-kit.doctor')->expectsOutputToContain('Campaigns name a theme that is not installed')->assertFailed();
});

it('uses themes.default for campaigns without a theme of their own', function (): void {
    config()->set('laranail.waitlist-kit.themes.default', KitThemes::PLAIN);
    everyBlock('', CampaignType::Waitlist);
    Campaign::query()->sole()->update(['theme' => null]);

    $this->get('/waitlist/launch')->assertSee('data-theme="' . KitThemes::PLAIN . '"', false);
});

it('carries the application\'s CSP nonce to the theme\'s tags and inline settings', function (): void {
    app(Vite::class)->useCspNonce('n0nce123');
    everyBlock(KitThemes::DEFAULT, CampaignType::Waitlist);

    $html = $this->get('/waitlist/launch')->getContent();

    expect($html)->toContain('<style  nonce="n0nce123"')
        ->toMatch('/<script[^>]+nonce="n0nce123"/')
        ->toMatch('/<link[^>]+nonce="n0nce123"/');
});

it('renders unstyled rather than failing when the assets are not published, and the doctor says so', function (): void {
    (new Filesystem)->deleteDirectory(public_path('vendor/waitlist-kit'));
    everyBlock(KitThemes::DEFAULT, CampaignType::Waitlist);

    $this->get('/waitlist/launch')->assertOk()->assertDontSee('/vendor/waitlist-kit/assets/', false);
    $this->artisan('laranail::waitlist-kit.doctor')->expectsOutputToContain('assets are not published')->assertFailed();
});

it('refuses a theme or a setting named outside the convention', function (): void {
    expect(fn (): Theme => new Theme('Fancy Theme', 'x', ThemeStyling::Css, 'v', []))->toThrow(InvalidArgumentException::class)
        ->and(fn (): ThemeSetting => ThemeSetting::color('Primary Colour', '#000'))->toThrow(InvalidArgumentException::class);
});

it('leaves the application\'s own Vite settings alone', function (): void {
    $vite = app(Vite::class);
    $before = $vite->hotFile();
    everyBlock(KitThemes::DEFAULT, CampaignType::Waitlist);

    $this->get('/waitlist/launch')->assertOk();

    expect(app(Vite::class)->hotFile())->toBe($before);
});
