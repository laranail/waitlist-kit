<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use Simtabi\Laranail\WaitlistKit\Models\Admin;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Database\Seeders\WaitlistKitSeeder;
use Simtabi\Laranail\WaitlistKit\Database\Seeders\WaitlistKitAdminSeeder;

/** Runs the baseline seeder through db:seed, as an application does, and returns what it printed. */
function seedWithOutput(): string
{
    Artisan::call('db:seed', ['--class' => WaitlistKitSeeder::class, '--force' => true]);

    return Artisan::output();
}

beforeEach(function (): void {
    config()->set('laranail.waitlist-kit.auth.mode', 'bundled');
    config()->set('laranail.waitlist-kit.seeding.admin_email', ' Owner@Example.org ');
});

it('creates the first administrator once, with a password shown once that signs in', function (): void {
    $output = seedWithOutput();

    expect(preg_match('/Password, shown once: (\S+)/', $output, $match))->toBe(1);
    $password = $match[1];

    expect(Admin::query()->sole()->email)->toBe('owner@example.org')
        ->and(mb_strlen($password))->toBe(WaitlistKitAdminSeeder::PASSWORD_LENGTH)
        ->and(Auth::guard(KitConfig::BUNDLED_GUARD)->validate(['email' => 'owner@example.org', 'password' => $password]))->toBeTrue()
        ->and($output)->toContain('laranail::waitlist-kit.admin owner@example.org');

    // A second run neither adds an administrator nor prints a password.
    expect(seedWithOutput())->not->toContain('shown once')
        ->and(Admin::query()->count())->toBe(1);
});

it('seeds no administrator in host sign-in, without an address, or when one exists', function (): void {
    config()->set('laranail.waitlist-kit.auth.mode', 'host');
    expect(seedWithOutput())->not->toContain('shown once');

    config()->set('laranail.waitlist-kit.auth.mode', 'bundled');
    config()->set('laranail.waitlist-kit.seeding.admin_email', 'not an address');
    expect(seedWithOutput())->not->toContain('shown once');

    config()->set('laranail.waitlist-kit.seeding.admin_email', 'owner@example.org');
    Admin::factory()->create(['email' => 'someone@example.org']);
    expect(seedWithOutput())->not->toContain('shown once')
        ->and(Admin::query()->pluck('email')->all())->toBe(['someone@example.org']);
});

it('generates a different password on every install', function (): void {
    preg_match('/shown once: (\S+)/', seedWithOutput(), $first);
    Admin::query()->delete();
    preg_match('/shown once: (\S+)/', seedWithOutput(), $second);

    expect($first[1])->not->toBe($second[1]);
});
