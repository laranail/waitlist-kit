<?php

declare(strict_types=1);

use Simtabi\Laranail\Package\Tools\Testing\NamingScope;
use Simtabi\Laranail\Package\Tools\Testing\AssertsRegisteredNames;

uses(AssertsRegisteredNames::class);

it('scopes every command name to the package', function (): void {
    $scope = NamingScope::for('laranail/waitlist-kit', 'Simtabi\\Laranail\\WaitlistKit\\');

    $this->assertCommandNamesScoped($scope, atLeast: 2);
});

it('reads its config under the vendor key', function (): void {
    expect(config('laranail.waitlist-kit.campaigns.mode'))->toBe('multi')
        ->and(config('waitlist-kit'))->toBeNull();
});
