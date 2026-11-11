<?php

declare(strict_types=1);

use Simtabi\Laranail\Package\Tools\Testing\NamingScope;
use Simtabi\Laranail\Package\Tools\Testing\AssertsRegisteredNames;

uses(AssertsRegisteredNames::class);

it('scopes every command name to the package', function (): void {
    $scope = NamingScope::for('laranail/waitlist-kit', 'Simtabi\\Laranail\\WaitlistKit\\');

    $this->assertCommandNamesScoped($scope, atLeast: 4);
});

it('scopes every route name, rate limiter, view and translation namespace', function (): void {
    $scope = NamingScope::for('laranail/waitlist-kit', 'Simtabi\\Laranail\\WaitlistKit\\');

    $this->assertRouteNamesScoped($scope, atLeast: 4);
    $this->assertRateLimitersScoped($scope, atLeast: 2);
    $this->assertBladeComponentsScoped($scope, atLeast: 1); // one namespace holds both components
    $this->assertViewNamespacesScoped($scope, atLeast: 1);
    $this->assertTranslationNamespacesScoped($scope, atLeast: 1);
});

it('reads its config under the vendor key', function (): void {
    expect(config('laranail.waitlist-kit.campaigns.mode'))->toBe('multi')
        ->and(config('waitlist-kit'))->toBeNull();
});
