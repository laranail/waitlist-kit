<?php

declare(strict_types=1);

use Simtabi\Laranail\WaitlistKit\Data\ConversionSettings;

it('defaults to no double opt-in and referrals of one place, at most ten', function (): void {
    expect(ConversionSettings::fromArray([])->toArray())
        ->toBe(['double_opt_in' => false, 'referrals' => true, 'referral_bump' => 1, 'referral_cap' => 10]);
});

it('reads stored values, and takes the default for a wrong type or an out-of-range number', function (): void {
    $stored = ConversionSettings::fromArray(['double_opt_in' => true, 'referrals' => false, 'referral_bump' => 5, 'referral_cap' => 50]);
    $broken = ConversionSettings::fromArray(['double_opt_in' => 'yes', 'referrals' => 1, 'referral_bump' => -3, 'referral_cap' => '20']);

    expect($stored->toArray())->toBe(['double_opt_in' => true, 'referrals' => false, 'referral_bump' => 5, 'referral_cap' => 50])
        ->and($broken->toArray())->toBe(ConversionSettings::fromArray([])->toArray());
});

it('cannot be built out of range', function (int $bump, int $cap): void {
    expect(fn (): ConversionSettings => new ConversionSettings(referralBump: $bump, referralCap: $cap))->toThrow(InvalidArgumentException::class);
})->with([
    'negative bump' => [-1, 10],
    'bump too big'  => [ConversionSettings::MAX_BUMP + 1, 10],
    'negative cap'  => [1, -1],
    'cap too big'   => [1, ConversionSettings::MAX_CAP + 1],
]);
