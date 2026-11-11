<?php

declare(strict_types=1);

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\WaitlistKit\Auth\Abilities;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Providers\WaitlistKitServiceProvider;

it('sends a guest to the application\'s login, or refuses when it has none', function (): void {
    $this->get('/waitlist-admin')->assertForbidden();

    Route::get('login', fn (): string => 'login')->name('login');
    app('router')->getRoutes()->refreshNameLookups();

    $this->get('/waitlist-admin')->assertRedirect('/login');
});

it('admits only the addresses in admin.emails by default', function (): void {
    config()->set('laranail.waitlist-kit.admin.emails', ['owner@example.org']);

    $this->actingAs(new GenericUser(['id' => 2, 'email' => 'someone@example.org']), 'web')->get('/waitlist-admin')->assertForbidden();
    $this->actingAs(new GenericUser(['id' => 1, 'email' => 'Owner@Example.org']), 'web')->get('/waitlist-admin')->assertOk();
});

it('keeps an application\'s own ability definitions', function (): void {
    expect(Gate::has(Abilities::VIEW))->toBeTrue();

    Gate::define(Abilities::VIEW, fn ($user): bool => $user->email === 'role@example.org');

    $this->actingAs(new GenericUser(['id' => 3, 'email' => 'role@example.org']), 'web')->get('/waitlist-admin')->assertOk();
});

it('does not overwrite an ability the application defined before the kit booted', function (): void {
    // The provider only fills in missing abilities: re-running it after an app definition leaves the app's in place.
    Gate::define(Abilities::ERASE, fn (): bool => false);
    new ReflectionMethod(WaitlistKitServiceProvider::class, 'defineAbilities')
        ->invoke(app()->getProvider(WaitlistKitServiceProvider::class), app(KitConfig::class));

    signInAdmin('host');
    expect(Gate::allows(Abilities::ERASE))->toBeFalse()
        ->and(Gate::allows(Abilities::MANAGE))->toBeTrue();
});

it('answers 404 for the bundled login in host mode', function (): void {
    $this->get('/waitlist-admin/login')->assertNotFound();
    $this->post('/waitlist-admin/login', ['email' => 'owner@example.org', 'password' => bin2hex(random_bytes(8))])->assertNotFound();
});
