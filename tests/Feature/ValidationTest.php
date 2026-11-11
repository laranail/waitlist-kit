<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;
use Simtabi\Laranail\WaitlistKit\Actions\CreateCampaign;

it('refuses a campaign whose fields break the rules', function (string $slug, array $locales, string $field): void {
    try {
        app(CreateCampaign::class)->handle('Launch', $slug, CampaignType::Waitlist, $locales);
        $this->fail('Expected a ValidationException.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey($field);
    }
})->with([
    'slug with spaces'    => ['my launch', ['en'], 'slug'],
    'slug with uppercase' => ['Launch', ['en'], 'slug'],
    'no locales'          => ['launch', [], 'locales'],
    'a malformed locale'  => ['launch', ['english'], 'locales.0'],
    'a repeated locale'   => ['launch', ['en', 'en'], 'locales.1'],
]);

it('refuses a slug another campaign has', function (): void {
    app(CreateCampaign::class)->handle('One', 'launch', CampaignType::Waitlist);

    expect(fn () => app(CreateCampaign::class)->handle('Two', 'launch', CampaignType::Waitlist))
        ->toThrow(ValidationException::class);
});

it('accepts a regional locale', function (): void {
    expect(app(CreateCampaign::class)->handle('Launch', 'launch', CampaignType::Waitlist, ['en', 'pt-BR'])->locales)
        ->toBe(['en', 'pt-BR']);
});
