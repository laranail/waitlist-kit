<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('passes the doctor on a migrated install', function (): void {
    $this->artisan('laranail::waitlist-kit.doctor')->assertSuccessful();
});

it('fails the doctor on a configuration value nobody chose', function (string $key, string $value, string $message): void {
    config()->set("laranail.waitlist-kit.{$key}", $value);

    $this->artisan('laranail::waitlist-kit.doctor')
        ->expectsOutputToContain($message)
        ->assertFailed();
})->with([
    'campaigns mode' => ['campaigns.mode', 'many', 'campaigns.mode is "many"'],
    'auth mode'      => ['auth.mode', 'both', 'auth.mode is "both"'],
    'auth guard'     => ['auth.guard', 'nowhere', 'auth.guard names "nowhere"'],
]);

it('fails the doctor when limited has no ceiling', function (): void {
    config()->set('laranail.waitlist-kit.campaigns.mode', 'limited');
    config()->set('laranail.waitlist-kit.campaigns.max', 0);

    $this->artisan('laranail::waitlist-kit.doctor')
        ->expectsOutputToContain('campaigns.max must be at least 1')
        ->assertFailed();
});

it('fails the doctor when a table is missing', function (): void {
    Schema::drop('laranail_waitlist_kit_events');

    $this->artisan('laranail::waitlist-kit.doctor')
        ->expectsOutputToContain('Missing tables: laranail_waitlist_kit_events')
        ->assertFailed();
});

it('installs: publishes the config and checks the result', function (): void {
    $published = config_path('laranail/waitlist-kit.php');
    @unlink($published);

    $this->artisan('laranail::waitlist-kit.install')->assertSuccessful();

    expect($published)->toBeFile();
    expect(require $published)->toHaveKeys(['database', 'campaigns', 'auth']);
    @unlink($published);
});
