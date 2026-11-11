<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Tests\Feature\Admin;

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Simtabi\Laranail\WaitlistKit\Auth\Abilities;
use Simtabi\Laranail\WaitlistKit\Tests\TestCase;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Themes\KitThemes;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Mail\EnquiryNotice;
use Simtabi\Laranail\WaitlistKit\Settings\SettingsStore;
use Simtabi\Laranail\DbTools\Providers\DbToolsServiceProvider;
use Simtabi\Laranail\Settings\Providers\SettingsServiceProvider;
use Simtabi\Laranail\WaitlistKit\Settings\LaranailSettingsStore;
use Simtabi\Laranail\Enumerator\Providers\EnumeratorServiceProvider;

/** settings.store = database with laranail/settings installed: the backend's saved values override the configuration. */
final class SettingsDatabaseTest extends TestCase
{
    public function test_it_uses_laranail_settings(): void
    {
        $this->assertInstanceOf(LaranailSettingsStore::class, app(SettingsStore::class));
        $this->artisan('laranail::waitlist-kit.doctor')->assertSuccessful();
    }

    public function test_saved_values_override_the_configuration_everywhere(): void
    {
        $this->signIn();
        Mail::fake();
        config()->set('laranail.waitlist-kit.contact.to', 'config@example.org');

        $this->put('/waitlist-admin/settings', $this->form([
            'mail'    => ['from_address' => 'hello@example.org', 'from_name' => 'Northwind'],
            'contact' => ['to' => 'inbox@example.org', 'topics' => 'press, demo', 'company' => 'off'],
            'themes'  => ['default' => KitThemes::PLAIN],
        ]))->assertRedirect('/waitlist-admin/settings');

        $config = app(KitConfig::class);
        $this->assertSame('inbox@example.org', $config->contactInbox());
        $this->assertSame(['press', 'demo'], $config->contactTopics());
        $this->assertSame('off', $config->contactCompany());
        $this->assertSame(KitThemes::PLAIN, $config->defaultTheme());

        $this->postJson('/waitlist/contact', ['name' => 'Dana', 'email' => 'dana@example.com', 'topic' => 'demo', 'message' => 'Twelve hosts, please.', 'agree' => '1'])->assertCreated();
        Mail::assertQueued(EnquiryNotice::class, fn (EnquiryNotice $mail): bool => $mail->hasTo('inbox@example.org') && $mail->hasFrom('hello@example.org', 'Northwind'));

        Campaign::factory()->live()->create(['slug' => 'launch', 'theme' => null]);
        $this->get('/waitlist/launch')->assertSee('data-theme="' . KitThemes::PLAIN . '"', false);
    }

    public function test_an_emptied_field_falls_back_to_the_configuration(): void
    {
        $this->signIn();
        config()->set('laranail.waitlist-kit.contact.to', 'config@example.org');

        $this->put('/waitlist-admin/settings', $this->form(['contact' => ['to' => 'inbox@example.org']]));
        $this->assertSame('inbox@example.org', app(KitConfig::class)->contactInbox());

        $this->put('/waitlist-admin/settings', $this->form(['contact' => ['to' => '']]));
        $this->assertSame('config@example.org', app(KitConfig::class)->contactInbox());
    }

    public function test_a_saved_rate_limit_applies_to_the_next_sign_up(): void
    {
        $this->signIn();
        $this->put('/waitlist-admin/settings', $this->form(['signups' => ['per_minute' => '1']]));
        Campaign::factory()->live()->create(['slug' => 'launch']);
        Mail::fake();

        $this->postJson('/waitlist/launch', ['email' => 'a@example.com'])->assertCreated();
        $this->postJson('/waitlist/launch', ['email' => 'b@example.com'])->assertTooManyRequests();
    }

    public function test_invalid_values_are_refused(): void
    {
        $this->signIn();

        $this->put('/waitlist-admin/settings', $this->form([
            'mail'    => ['from_address' => 'not-an-address'],
            'signups' => ['per_minute' => '0'],
            'contact' => ['topics' => 'Sales Team!', 'company' => 'sometimes'],
            'themes'  => ['default' => 'gone/theme'],
        ]))->assertSessionHasErrors(['mail.from_address', 'signups.per_minute', 'contact.topics', 'contact.company', 'themes.default']);
    }

    public function test_saving_takes_the_manage_ability(): void
    {
        $this->signIn();
        Gate::define(Abilities::MANAGE, fn (): bool => false);

        $this->get('/waitlist-admin/settings')->assertOk();
        $this->put('/waitlist-admin/settings', $this->form())->assertForbidden();
    }

    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        // What package discovery registers in a real application: laranail/settings and the providers it relies on.
        return [...parent::getPackageProviders($app), DbToolsServiceProvider::class, EnumeratorServiceProvider::class, SettingsServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app->make('config')->set('laranail.waitlist-kit.settings.store', 'database');
    }

    protected function defineDatabaseMigrations(): void
    {
        // Once per database: on MySQL and PostgreSQL the tables outlive RefreshDatabase's per-test transaction (MySQL
        // commits DDL), so creating them for every test fails on the second; SQLite in memory starts empty each time.
        if (! Schema::hasTable('settings_categories')) {
            (require dirname(__DIR__, 3) . '/vendor/laranail/settings/database/migrations/create_laranail_settings_tables.php')->up();
        }
    }

    /** @return array<string, mixed> */
    private function form(array $overrides = []): array
    {
        return array_replace_recursive([
            'mail'     => ['from_address' => '', 'from_name' => ''],
            'signups'  => ['thank_you' => '1', 'per_minute' => '10'],
            'contact'  => ['enabled' => '1', 'to' => '', 'topics' => 'sales, support', 'company' => 'optional', 'consent' => '1', 'per_minute' => '5'],
            'security' => ['captcha' => '0'],
            'themes'   => ['default' => KitThemes::DEFAULT],
        ], $overrides);
    }

    private function signIn(): void
    {
        config()->set('laranail.waitlist-kit.admin.emails', ['owner@example.org']);
        $this->actingAs(new GenericUser(['id' => 1, 'name' => 'Owner', 'email' => 'owner@example.org']), 'web');
    }
}
