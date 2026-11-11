<?php

declare(strict_types=1);

use Simtabi\Laranail\Package\Tools\Testing\AssertsDocsConformance;

uses(AssertsDocsConformance::class);

it('follows the docs standard', function (): void {
    $this->assertDocsConformToStandard(dirname(__DIR__, 2), minimumPages: 10);
    expect(true)->toBeTrue();
});
