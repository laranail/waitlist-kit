<?php

declare(strict_types=1);

use Illuminate\Mail\MailManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Simtabi\Laranail\WaitlistKit\Mail\ThankYouMail;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;

beforeEach(function (): void {
    Mail::fake();
    config()->set('app.name', 'Northwind');
});

it('thanks in the language the sign-up was made in', function (): void {
    liveCampaign(locales: ['en', 'es', 'ja']);

    test()->postJson('/waitlist/launch', ['email' => 'es@example.com', 'locale' => 'es']);
    test()->postJson('/waitlist/launch', ['email' => 'ja@example.com', 'locale' => 'ja']);

    Mail::assertQueued(ThankYouMail::class, fn (ThankYouMail $mail): bool => $mail->hasTo('es@example.com') && $mail->locale === 'es');
    Mail::assertQueued(ThankYouMail::class, fn (ThankYouMail $mail): bool => $mail->hasTo('ja@example.com') && $mail->locale === 'ja');
});

it('renders the words of that language, with the site name', function (): void {
    $mail = new ThankYouMail('Northwind', 'Launch')->locale('es');

    $mail->assertHasSubject('Ya estás en la lista de espera de Northwind');
    $mail->assertSeeInText('Gracias por unirte a la lista de espera de Northwind');
    $mail->assertSeeInText('El equipo de Northwind');
});

it('sends nothing with the thank-you turned off', function (): void {
    config()->set('laranail.waitlist-kit.signups.thank_you', false);
    liveCampaign();

    test()->postJson('/waitlist/launch', ['email' => 'dana@example.com'])->assertCreated();

    Mail::assertNothingQueued();
});

it('keeps a sign-up whose mail cannot be sent, and does not show it', function (): void {
    Mail::swap(new class extends MailManager
    {
        public function __construct() {}

        public function mailer($name = null): never
        {
            throw new RuntimeException('550 5.1.1 <dana@example.com>: Recipient address rejected');
        }
    });
    Log::spy();
    liveCampaign();

    test()->postJson('/waitlist/launch', ['email' => 'dana@example.com'])->assertCreated();

    expect(Subscriber::query()->count())->toBe(1);
    Log::shouldHaveReceived('warning')->withArgs(
        fn (string $message, array $context): bool => ! str_contains(json_encode($context['subscriber'] ?? ''), '@'),
    );
});
