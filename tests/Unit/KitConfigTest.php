<?php

declare(strict_types=1);

use Illuminate\Config\Repository;
use Simtabi\Laranail\WaitlistKit\Enums\AuthMode;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;

function kitConfig(array $kit, array $guards = ['web' => ['driver' => 'session']]): KitConfig
{
    return new KitConfig(new Repository([
        'laranail' => ['waitlist-kit' => array_replace_recursive(require dirname(__DIR__, 2) . '/config/waitlist-kit.php', $kit)],
        'auth'     => ['guards' => $guards],
    ]));
}

it('turns the campaigns mode into a limit', function (string $mode, int $max, ?int $limit): void {
    expect(kitConfig(['campaigns' => ['mode' => $mode, 'max' => $max]])->campaignLimit())->toBe($limit);
})->with([
    'single'  => ['single', 9, 1],
    'limited' => ['limited', 4, 4],
    'multi'   => ['multi', 4, null],
]);

it('has no problems with its own defaults', function (): void {
    expect(kitConfig([])->problems())->toBe([])
        ->and(kitConfig([])->authMode())->toBe(AuthMode::Host)
        ->and(kitConfig([])->tablePrefix())->toBe('laranail_waitlist_kit_')
        ->and(kitConfig([])->connection())->toBeNull();
});

it('accepts a bundled sign-in without a host guard', function (): void {
    expect(kitConfig(['auth' => ['mode' => 'bundled', 'guard' => 'nowhere']], guards: [])->problems())->toBe([]);
});
