<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Simtabi\Laranail\WaitlistKit\Mail\InviteMail;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Mail\ThankYouMail;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Models\FunnelEvent;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;

beforeEach(fn () => Mail::fake());

/** @return array{0: Campaign, 1: list<Subscriber>} a live campaign with an invite URL and $n subscribers on its list */
function listOf(int $n, ?string $inviteUrl = 'https://app.example.com/join'): array
{
    $campaign = liveCampaign();
    $campaign->update(['settings' => ['conversion' => ['invite_url' => $inviteUrl]]]);
    foreach (range(1, $n) as $i) {
        test()->postJson('/waitlist/launch', ['email' => "p{$i}@example.com"])->assertCreated();
    }

    return [$campaign, Subscriber::query()->orderBy('position')->get()->all()];
}

function unsubscribeLinkFor(string $email): string
{
    $link = '';
    Mail::assertQueued(ThankYouMail::class, function (ThankYouMail $mail) use ($email, &$link): bool {
        $link = (string) $mail->unsubscribeUrl;

        return $mail->hasTo($email);
    });

    return $link;
}

it('puts a signed unsubscribe link in the welcome mail, with one-click headers', function (): void {
    listOf(1);
    $link = unsubscribeLinkFor('p1@example.com');
    $mail = new ThankYouMail('Northwind', 'Launch', null, null, $link);

    expect($link)->toContain('/waitlist/launch/unsubscribe/')->toContain('signature=')
        ->and($mail->headers()->text)->toBe(['List-Unsubscribe' => '<' . $link . '>', 'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click'])
        ->and($mail->render())->toContain('To leave the list: ' . $link);
});

it('asks before unsubscribing, then takes them off once, for good', function (): void {
    [$campaign] = listOf(1);
    $link = unsubscribeLinkFor('p1@example.com');

    $this->get($link)->assertOk()->assertSee('Leave the list')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    expect(Subscriber::query()->sole()->status)->toBe(SubscriberStatus::Confirmed);

    $this->post($link)->assertOk()->assertSee('left the ' . $campaign->name . ' list');
    $this->post($link)->assertOk();

    expect(Subscriber::query()->sole()->status)->toBe(SubscriberStatus::Unsubscribed)
        ->and(FunnelEvent::query()->where('type', FunnelEventType::Unsubscribe)->count())->toBe(1);
});

it('refuses a tampered or misplaced unsubscribe link', function (): void {
    listOf(1);
    $link = unsubscribeLinkFor('p1@example.com');
    Campaign::factory()->live()->create(['slug' => 'other']);

    $this->post(preg_replace('/signature=[^&]+/', 'signature=0000', $link))->assertForbidden();
    $this->post(str_replace('/waitlist/launch/', '/waitlist/other/', $link))->assertNotFound();
    expect(Subscriber::query()->sole()->status)->toBe(SubscriberStatus::Confirmed);
});

it('invites the next confirmed subscribers in list order, never twice, and never someone pending or gone', function (): void {
    [$campaign, $list] = listOf(4);
    $list[1]->update(['status' => SubscriberStatus::Unsubscribed]);
    Subscriber::factory()->create(['campaign_id' => $campaign->id, 'status' => SubscriberStatus::Pending, 'position' => null]);
    signInAdmin('host');

    $this->post("/waitlist-admin/campaigns/{$campaign->id}/invites", ['count' => 2])->assertSessionHas('status', 'Invited 2 subscribers.');
    $this->post("/waitlist-admin/campaigns/{$campaign->id}/invites", ['count' => 10])->assertSessionHas('status', 'Invited 1 subscriber.');
    $this->post("/waitlist-admin/campaigns/{$campaign->id}/invites", ['count' => 10])->assertSessionHas('status', 'Nobody was left to invite.');

    expect(Subscriber::query()->where('status', SubscriberStatus::Invited)->orderBy('position')->pluck('email')->all())
        ->toBe(['p1@example.com', 'p3@example.com', 'p4@example.com'])
        ->and(FunnelEvent::query()->where('type', FunnelEventType::Invite)->count())->toBe(3);
    Mail::assertQueued(InviteMail::class, fn (InviteMail $mail): bool => $mail->hasTo('p1@example.com')
        && $mail->link === 'https://app.example.com/join?invite=' . $list[0]->referral_code
        && $mail->unsubscribeUrl !== null);
    Mail::assertQueued(InviteMail::class, 3);
});

it('refuses to invite without an invite URL, or without the manage ability', function (): void {
    [$campaign] = listOf(1, inviteUrl: null);
    signInAdmin('host');

    $this->post("/waitlist-admin/campaigns/{$campaign->id}/invites", ['count' => 1])->assertSessionHasErrors('count');
    $this->post("/waitlist-admin/campaigns/{$campaign->id}/invites", ['count' => 0])->assertSessionHasErrors('count');

    Gate::define('laranail-waitlist-kit.manage', fn (): bool => false);
    $this->post("/waitlist-admin/campaigns/{$campaign->id}/invites", ['count' => 1])->assertForbidden();
    Mail::assertNotQueued(InviteMail::class);
});

it('marks a subscriber converted when the product reports it with the token', function (): void {
    [$campaign, $list] = listOf(1);
    config()->set('laranail.waitlist-kit.conversion.token', $token = bin2hex(random_bytes(16)));
    $body = ['campaign' => 'launch', 'invite' => $list[0]->referral_code];

    $this->postJson('/waitlist/api/conversions', $body)->assertUnauthorized();
    $this->postJson('/waitlist/api/conversions', $body, ['Authorization' => 'Bearer wrong'])->assertUnauthorized();
    $this->postJson('/waitlist/api/conversions', [...$body, 'invite' => 'zzzzzzzzzz'], ['Authorization' => 'Bearer ' . $token])->assertNotFound();

    $response = $this->postJson('/waitlist/api/conversions', $body, ['Authorization' => 'Bearer ' . $token])->assertNoContent();
    $this->postJson('/waitlist/api/conversions', $body, ['Authorization' => 'Bearer ' . $token])->assertNoContent();

    expect(Subscriber::query()->sole()->status)->toBe(SubscriberStatus::Converted)
        ->and(FunnelEvent::query()->where('type', FunnelEventType::Convert)->count())->toBe(1)
        ->and($response->headers->getCookies())->toBe([]);
});

it('has no conversion endpoint until a token is set', function (): void {
    [, $list] = listOf(1);

    $this->postJson('/waitlist/api/conversions', ['campaign' => 'launch', 'invite' => $list[0]->referral_code], ['Authorization' => 'Bearer '])->assertNotFound();
});

it('saves a campaign\'s conversion settings from its screen, keeping its other settings', function (): void {
    $campaign = liveCampaign();
    $campaign->update(['settings' => ['keep' => 'me']]);
    signInAdmin('host');

    $this->put("/waitlist-admin/campaigns/{$campaign->id}/conversion", ['double_opt_in' => '1', 'referrals' => '0', 'referral_bump' => 3, 'referral_cap' => 7, 'invite_url' => 'https://app.example.com/join'])
        ->assertRedirect();
    $this->put("/waitlist-admin/campaigns/{$campaign->id}/conversion", ['double_opt_in' => '1', 'referrals' => '1', 'referral_bump' => 3, 'referral_cap' => 7, 'invite_url' => 'http://insecure.example.com'])
        ->assertSessionHasErrors('invite_url');

    expect($campaign->refresh()->settings['keep'])->toBe('me')
        ->and($campaign->conversion()->toArray())->toBe(['double_opt_in' => true, 'referrals' => false, 'referral_bump' => 3, 'referral_cap' => 7, 'invite_url' => 'https://app.example.com/join']);
    $this->get("/waitlist-admin/campaigns/{$campaign->id}/edit")->assertOk()->assertSee('id="conversion"', false);
});

it('renders the invitation in the subscriber\'s language, with the link and the way out', function (): void {
    $mail = new InviteMail('Northwind', 'Launch', 'https://app.example.com/join?invite=abc', null, 'https://northwind.example/unsubscribe');

    expect($mail->render())->toContain('Your access is ready:')->toContain('https://app.example.com/join?invite=abc')
        ->toContain('To leave the list: https://northwind.example/unsubscribe')
        ->and($mail->locale('fr')->render())->toContain('Votre accès est prêt')
        ->and($mail->envelope()->subject)->toBe('Your access to Northwind is ready')
        ->and($mail->headers()->text)->toHaveKey('List-Unsubscribe-Post');
});
