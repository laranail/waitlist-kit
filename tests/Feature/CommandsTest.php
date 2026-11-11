<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Simtabi\Laranail\WaitlistKit\Models\Enquiry;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Models\FunnelEvent;
use Simtabi\Laranail\WaitlistKit\Models\Integration;

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

it('fails the doctor when an integration names an unknown driver', function (): void {
    Integration::factory()->create(['driver' => 'mailchimp']);

    $this->artisan('laranail::waitlist-kit.doctor')
        ->expectsOutputToContain('Integrations name unknown drivers: mailchimp')
        ->assertFailed();
});

it('exports a campaign as CSV with formula cells quoted', function (): void {
    $campaign = liveCampaign();
    Subscriber::factory()->create(['campaign_id' => $campaign->id, 'email' => 'dana@example.com', 'referral_code' => '=CMD']);
    $file = tempnam(sys_get_temp_dir(), 'wk') ?: '';

    $this->artisan('laranail::waitlist-kit.export', ['campaign' => 'launch', '--output' => $file])->assertSuccessful();

    $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];
    @unlink($file);
    expect($lines[0])->toBe('id,email,status,locale,position,referral_code,referred_by_id,created_at')
        ->and($lines[1])->toContain(",dana@example.com,pending,en,,'=CMD,,");

    $this->artisan('laranail::waitlist-kit.export', ['campaign' => 'nowhere'])->assertFailed();
    $this->artisan('laranail::waitlist-kit.export')->assertFailed();
});

it('exports the enquiries, and forget erases them', function (): void {
    Enquiry::factory()->create(['email' => 'dana@example.com', 'message' => '=HYPERLINK("x") please']);
    $file = tempnam(sys_get_temp_dir(), 'wk') ?: '';

    $this->artisan('laranail::waitlist-kit.export', ['--enquiries' => true, '--output' => $file])->assertSuccessful();
    $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];
    @unlink($file);
    expect($lines[0])->toBe('id,name,email,company,topic,message,locale,created_at')
        ->and($lines[1])->toContain('"\'=HYPERLINK(""x"") please"');

    $this->artisan('laranail::waitlist-kit.forget', ['email' => 'dana@example.com'])
        ->expectsOutputToContain('0 subscriber record(s) and 1 enquiry(ies) erased')->assertSuccessful();
    expect(Enquiry::query()->count())->toBe(0);
});

it('forgets an address on every campaign, with its funnel events', function (): void {
    $a = liveCampaign();
    $b = liveCampaign('other');
    $dana = Subscriber::factory()->create(['campaign_id' => $a->id, 'email' => 'dana@example.com']);
    $trashed = Subscriber::factory()->create(['campaign_id' => $b->id, 'email' => 'dana@example.com']);
    $trashed->delete();
    $referred = Subscriber::factory()->referredBy($dana)->create();
    FunnelEvent::factory()->about($dana)->create();

    $this->artisan('laranail::waitlist-kit.forget', ['email' => ' DANA@example.com'])
        ->expectsOutputToContain('2 subscriber record(s) and 0 enquiry(ies) erased')
        ->assertSuccessful();

    expect(Subscriber::withTrashed()->where('email', 'dana@example.com')->count())->toBe(0)
        ->and(FunnelEvent::query()->where('eventable_id', $dana->id)->count())->toBe(0)
        ->and($referred->fresh()?->referred_by_id)->toBeNull();

    $this->artisan('laranail::waitlist-kit.forget', ['email' => 'not-an-address'])->assertFailed();
});
