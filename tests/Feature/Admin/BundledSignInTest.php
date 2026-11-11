<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Tests\Feature\Admin;

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Hash;
use Simtabi\Laranail\WaitlistKit\Models\Admin;
use Simtabi\Laranail\WaitlistKit\Tests\TestCase;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;

/** auth.mode = bundled from boot, so the bundled login routes are registered as on a real install. */
final class BundledSignInTest extends TestCase
{
    public function test_a_guest_goes_to_the_bundled_login(): void
    {
        $this->get('/waitlist-admin')->assertRedirect('/waitlist-admin/login');
        $this->get('/waitlist-admin/login')->assertOk()->assertSee('name="password"', false);
    }

    public function test_an_administrator_signs_in_and_out(): void
    {
        $password = bin2hex(random_bytes(12));
        Admin::factory()->create(['email' => 'owner@example.org', 'password' => $password]);

        $this->post('/waitlist-admin/login', ['email' => 'Owner@example.org', 'password' => $password])->assertRedirect('/waitlist-admin');
        $this->assertAuthenticated(KitConfig::BUNDLED_GUARD);
        $this->get('/waitlist-admin')->assertOk();

        $this->post('/waitlist-admin/logout')->assertRedirect('/waitlist-admin/login');
        $this->assertGuest(KitConfig::BUNDLED_GUARD);
    }

    public function test_a_wrong_password_and_an_unknown_address_get_the_same_answer(): void
    {
        Admin::factory()->create(['email' => 'owner@example.org']);

        $wrong = $this->from('/waitlist-admin/login')->post('/waitlist-admin/login', ['email' => 'owner@example.org', 'password' => bin2hex(random_bytes(8))]);
        $unknown = $this->from('/waitlist-admin/login')->post('/waitlist-admin/login', ['email' => 'nobody@example.org', 'password' => bin2hex(random_bytes(8))]);

        $wrong->assertSessionHasErrors(['email' => 'Those details do not match an administrator.']);
        $unknown->assertSessionHasErrors(['email' => 'Those details do not match an administrator.']);
        $this->assertGuest(KitConfig::BUNDLED_GUARD);
    }

    public function test_sign_in_is_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/waitlist-admin/login', ['email' => 'owner@example.org', 'password' => bin2hex(random_bytes(8))]);
        }

        $this->post('/waitlist-admin/login', ['email' => 'owner@example.org', 'password' => bin2hex(random_bytes(8))])->assertTooManyRequests();
    }

    public function test_an_application_user_cannot_use_the_bundled_backend(): void
    {
        $this->actingAs(new GenericUser(['id' => 1, 'email' => 'owner@example.org']), 'web')
            ->get('/waitlist-admin')->assertRedirect('/waitlist-admin/login');
    }

    public function test_the_command_creates_an_administrator_with_a_prompted_password(): void
    {
        $password = bin2hex(random_bytes(10));

        $this->artisan('laranail::waitlist-kit.admin', ['email' => 'Owner@example.org', '--name' => 'Owner'])
            ->expectsQuestion('Password (at least 12 characters)', $password)
            ->expectsQuestion('The same password again', $password)
            ->expectsOutputToContain('Created owner@example.org')
            ->assertSuccessful();

        $admin = Admin::query()->where('email', 'owner@example.org')->sole();
        $this->assertTrue(Hash::check($password, $admin->password));
        $this->assertNotSame($password, $admin->password);

        $this->artisan('laranail::waitlist-kit.admin', ['email' => 'owner@example.org'])
            ->expectsQuestion('Password (at least 12 characters)', 'short')
            ->assertFailed();
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app->make('config')->set('laranail.waitlist-kit.auth.mode', 'bundled');
    }
}
