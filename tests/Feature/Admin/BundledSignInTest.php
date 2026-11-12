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
        $this->get('/admin/waitlist')->assertRedirect('/admin/waitlist/login');
        $this->get('/admin/waitlist/login')->assertOk()->assertSee('name="password"', false);
    }

    public function test_an_administrator_signs_in_and_out(): void
    {
        $password = bin2hex(random_bytes(12));
        Admin::factory()->create(['email' => 'owner@example.org', 'password' => $password]);

        $this->post('/admin/waitlist/login', ['email' => 'Owner@example.org', 'password' => $password])->assertRedirect('/admin/waitlist');
        $this->assertAuthenticated(KitConfig::BUNDLED_GUARD);
        $this->get('/admin/waitlist')->assertOk();

        $this->post('/admin/waitlist/logout')->assertRedirect('/admin/waitlist/login');
        $this->assertGuest(KitConfig::BUNDLED_GUARD);
    }

    public function test_a_wrong_password_and_an_unknown_address_get_the_same_answer(): void
    {
        Admin::factory()->create(['email' => 'owner@example.org']);

        $wrong = $this->from('/admin/waitlist/login')->post('/admin/waitlist/login', ['email' => 'owner@example.org', 'password' => bin2hex(random_bytes(8))]);
        $unknown = $this->from('/admin/waitlist/login')->post('/admin/waitlist/login', ['email' => 'nobody@example.org', 'password' => bin2hex(random_bytes(8))]);

        $wrong->assertSessionHasErrors(['email' => 'Those details do not match an administrator.']);
        $unknown->assertSessionHasErrors(['email' => 'Those details do not match an administrator.']);
        $this->assertGuest(KitConfig::BUNDLED_GUARD);
    }

    public function test_sign_in_is_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/waitlist/login', ['email' => 'owner@example.org', 'password' => bin2hex(random_bytes(8))]);
        }

        $this->post('/admin/waitlist/login', ['email' => 'owner@example.org', 'password' => bin2hex(random_bytes(8))])->assertTooManyRequests();
    }

    public function test_an_application_user_cannot_use_the_bundled_backend(): void
    {
        $this->actingAs(new GenericUser(['id' => 1, 'email' => 'owner@example.org']), 'web')
            ->get('/admin/waitlist')->assertRedirect('/admin/waitlist/login');
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
