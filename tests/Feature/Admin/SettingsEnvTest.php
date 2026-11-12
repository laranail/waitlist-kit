<?php

declare(strict_types=1);

it('shows the settings read-only, with the variable that sets each, on the env store', function (): void {
    signInAdmin('host');
    config()->set('laranail.waitlist-kit.contact.to', 'support@example.org');

    $this->get('/admin/waitlist/settings')->assertOk()
        ->assertSee('support@example.org')
        ->assertSee('WAITLIST_KIT_CONTACT_TO')
        ->assertSee('Contact inbox')
        ->assertDontSee('laranail/waitlist-kit::')
        ->assertDontSee('<form method="post" action="' . route('laranail-waitlist-kit.admin.settings.update') . '"', false);

    $this->put('/admin/waitlist/settings', ['contact' => ['to' => 'other@example.org']])->assertForbidden();
});

it('fails the doctor when the database store is asked for without laranail/settings', function (): void {
    config()->set('laranail.waitlist-kit.settings.store', 'database');

    $this->artisan('laranail::waitlist-kit.doctor')->expectsOutputToContain('laranail/settings is not installed')->assertFailed();
});
