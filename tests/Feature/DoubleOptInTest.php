<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Mail\ConfirmMail;
use Simtabi\Laranail\WaitlistKit\Mail\ThankYouMail;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Models\FunnelEvent;
use Simtabi\Laranail\WaitlistKit\Models\Integration;
use Simtabi\Laranail\WaitlistKit\Actions\AskToConfirm;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;
use Simtabi\Laranail\WaitlistKit\Jobs\MirrorSubscriber;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;
use Simtabi\Laranail\WaitlistKit\Http\Controllers\ConfirmController;

beforeEach(function (): void {
    Mail::fake();
    Bus::fake([MirrorSubscriber::class]);
    $campaign = liveCampaign();
    $campaign->update(['settings' => ['conversion' => ['double_opt_in' => true]]]);
    Integration::factory()->create(['driver' => 'app', 'settings' => ['endpoint' => 'https://hooks.example.com/in']]);
});

/** The link in the last confirmation mail sent to $email. */
function confirmLink(string $email): string
{
    $link = '';
    Mail::assertQueued(ConfirmMail::class, function (ConfirmMail $mail) use ($email, &$link): bool {
        $link = $mail->link;

        return $mail->hasTo($email);
    });

    return $link;
}

it('asks a new sign-up to confirm, and holds the welcome until it does', function (): void {
    $this->postJson('/waitlist/launch', ['email' => 'ada@example.com'])
        ->assertCreated()
        ->assertJson(['message' => __('laranail/waitlist-kit::page.check_email')]);

    $subscriber = Subscriber::query()->sole();
    expect($subscriber->status)->toBe(SubscriberStatus::Pending)
        ->and(confirmLink('ada@example.com'))->toContain('/waitlist/launch/confirm/' . $subscriber->id)->toContain('signature=');
    Mail::assertNotQueued(ThankYouMail::class);
    Bus::assertNotDispatched(MirrorSubscriber::class);
});

it('confirms from the link: a place, the funnel event, the welcome, and a page that says so', function (): void {
    $this->postJson('/waitlist/launch', ['email' => 'ada@example.com']);
    $link = confirmLink('ada@example.com');

    $this->get($link)->assertRedirect('/waitlist/launch')->assertHeader('Cache-Control', 'no-store, private');
    $this->get($link)->assertRedirect('/waitlist/launch'); // a second visit changes nothing

    $subscriber = Subscriber::query()->sole();
    expect($subscriber->status)->toBe(SubscriberStatus::Confirmed)
        ->and($subscriber->position)->toBe(1)
        ->and($subscriber->referral_code)->not->toBeNull()
        ->and(FunnelEvent::query()->where('type', FunnelEventType::Confirm)->count())->toBe(1);
    Mail::assertQueued(ThankYouMail::class, 1);
    Bus::assertDispatchedTimes(MirrorSubscriber::class, 1);

    $this->followingRedirects()->get($link)->assertOk();
    $this->withSession([ConfirmController::FLASH => 1])
        ->get('/waitlist/launch')
        ->assertSee(__('laranail/waitlist-kit::page.confirmed', ['position' => 1]));
});

it('refuses a tampered, expired or misplaced link', function (): void {
    $this->postJson('/waitlist/launch', ['email' => 'ada@example.com']);
    $link = confirmLink('ada@example.com');
    $other = Campaign::factory()->live()->create(['slug' => 'other']);

    $this->get(preg_replace('/signature=[^&]+/', 'signature=0000', $link))->assertForbidden();
    $this->get(str_replace('/waitlist/launch/', '/waitlist/other/', $link))->assertNotFound(); // scoped: not that campaign's

    $this->travel(AskToConfirm::LINK_DAYS + 1)->days();
    $this->get($link)->assertForbidden();

    expect(Subscriber::query()->sole()->status)->toBe(SubscriberStatus::Pending)
        ->and($other->subscribers()->count())->toBe(0);
});

it('re-sends a lost link when the address signs up again, at most once per window', function (): void {
    $this->postJson('/waitlist/launch', ['email' => 'ada@example.com']);
    $this->postJson('/waitlist/launch', ['email' => 'ada@example.com'])->assertCreated();
    Mail::assertQueued(ConfirmMail::class, 1);

    $this->travel(AskToConfirm::RESEND_MINUTES + 1)->minutes();
    $this->postJson('/waitlist/launch', ['email' => 'ada@example.com'])->assertCreated();
    Mail::assertQueued(ConfirmMail::class, 2);
});

it('does not confirm someone who has left the list', function (): void {
    $this->postJson('/waitlist/launch', ['email' => 'ada@example.com']);
    $link = confirmLink('ada@example.com');
    Subscriber::query()->sole()->update(['status' => SubscriberStatus::Unsubscribed]);

    $this->get($link)->assertRedirect('/waitlist/launch');

    expect(Subscriber::query()->sole()->status)->toBe(SubscriberStatus::Unsubscribed);
    Mail::assertNotQueued(ThankYouMail::class);
});

it('renders the confirmation mail in the subscriber\'s language, with the link and how long it works', function (): void {
    $mail = new ConfirmMail('Northwind', 'Launch', 'https://northwind.example/waitlist/launch/confirm/01x?signature=abc', 7);

    expect($mail->render())->toContain('https://northwind.example/waitlist/launch/confirm/01x?signature=abc')
        ->toContain('The link works for 7 days.')
        ->toContain('Please confirm your address to join the Northwind waitlist:')
        ->and($mail->locale('es')->render())->toContain('El enlace funciona durante 7 días.')
        ->and($mail->envelope()->subject)->toBe('Confirm your place on the Northwind waitlist');
});
