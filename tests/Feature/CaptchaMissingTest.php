<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Tests\Feature;

use Simtabi\Laranail\WaitlistKit\Tests\TestCase;
use Simtabi\Laranail\WaitlistKit\Providers\WaitlistKitServiceProvider;
use Simtabi\Laranail\Package\Tools\Providers\PackageToolsServiceProvider;

/** The application without laranail/captcha: turning security.captcha on must fail the doctor, not every sign-up. */
final class CaptchaMissingTest extends TestCase
{
    public function test_the_doctor_fails_when_captcha_is_on_but_not_installed(): void
    {
        config()->set('laranail.waitlist-kit.security.captcha', true);

        $this->artisan('laranail::waitlist-kit.doctor')
            ->expectsOutputToContain('laranail/captcha is not installed')
            ->assertFailed();
    }

    protected function getPackageProviders($app): array
    {
        return [PackageToolsServiceProvider::class, WaitlistKitServiceProvider::class];
    }
}
