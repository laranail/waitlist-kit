<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Commands\DoctorCommand;
use Illuminate\Database\UniqueConstraintViolationException;

it('creates every table under the configured prefix', function (): void {
    foreach (DoctorCommand::TABLES as $table) {
        expect(Schema::hasTable('laranail_waitlist_kit_' . $table))->toBeTrue("{$table} is missing");
    }
    expect(DoctorCommand::TABLES)->toHaveCount(8);
});

it('rolls every migration back', function (): void {
    Artisan::call('migrate:rollback', ['--force' => true]);

    foreach (DoctorCommand::TABLES as $table) {
        expect(Schema::hasTable('laranail_waitlist_kit_' . $table))->toBeFalse("{$table} survived the rollback");
    }
});

it('keeps one row per address per campaign', function (): void {
    $campaign = campaign();
    $campaign->subscribers()->create(['email' => 'dana@example.com']);

    // In a savepoint: on PostgreSQL a failed statement aborts the surrounding transaction (the test's own), and
    // every later statement would fail with it. The sign-up path must do the same with a duplicate.
    expect(fn () => DB::transaction(fn () => $campaign->subscribers()->create(['email' => 'dana@example.com'])))
        ->toThrow(UniqueConstraintViolationException::class);

    // The same address on another campaign is another lead.
    campaign('second')->subscribers()->create(['email' => 'dana@example.com']);
    expect(Subscriber::query()->count())->toBe(2);
});
