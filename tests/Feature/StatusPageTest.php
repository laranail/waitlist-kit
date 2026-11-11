<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Mail\ThankYouMail;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;

beforeEach(fn () => Mail::fake());

/** Signs $email up to the live `launch` campaign and returns the status link from their welcome mail. */
function statusLinkFor(string $email): string
{
    test()->postJson('/waitlist/launch', ['email' => $email])->assertCreated();
    $link = '';
    Mail::assertQueued(ThankYouMail::class, function (ThankYouMail $mail) use ($email, &$link): bool {
        $link = (string) $mail->statusUrl;

        return $mail->hasTo($email);
    });

    return $link;
}

it('links the welcome mail to a status page with the place, the referrals and the referral link', function (): void {
    liveCampaign();
    statusLinkFor('ada@example.com');
    $link = statusLinkFor('ben@example.com');
    $ben = Subscriber::query()->where('email', 'ben@example.com')->sole();
    Subscriber::factory()->create(['campaign_id' => $ben->campaign_id, 'referred_by_id' => $ben->id, 'position' => 9]);

    expect($link)->toBe(route('laranail-waitlist-kit.campaign.status', ['campaign' => 'launch', 'code' => $ben->referral_code]));

    $response = $this->get($link)->assertOk()
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertSee("You're number 2.")
        ->assertSee('1 person has joined with your link.')
        ->assertSee('value="' . route('laranail-waitlist-kit.campaign.show', ['campaign' => 'launch', 'ref' => $ben->referral_code]) . '"', false)
        ->assertSee('https://x.com/intent/post?url=', false)
        ->assertDontSee('ben@example.com');

    expect(referenceProblems((string) $response->getContent()))->toBe([])
        ->and(substr_count((string) $response->getContent(), '<h1'))->toBe(1);
});

it('renders in both themes', function (string $theme): void {
    liveCampaign()->update(['theme' => $theme]);

    $this->get(statusLinkFor('ada@example.com'))->assertOk()->assertSee('data-theme="' . $theme . '"', false);
})->with(['laranail/waitlist-kit:default', 'laranail/waitlist-kit:plain']);

it('has no page for an unknown code, another campaign\'s code, or someone who has left', function (): void {
    liveCampaign();
    $link = statusLinkFor('ada@example.com');
    $code = Subscriber::query()->sole()->referral_code;
    Campaign::factory()->live()->create(['slug' => 'other']);

    $this->get('/waitlist/launch/status/' . str_repeat('a', 10))->assertNotFound();
    $this->get('/waitlist/other/status/' . $code)->assertNotFound();

    Subscriber::query()->sole()->update(['status' => SubscriberStatus::Unsubscribed]);
    $this->get($link)->assertNotFound();
});

it('hides the share section when the campaign has referrals off', function (): void {
    liveCampaign()->update(['settings' => ['conversion' => ['referrals' => false]]]);

    $this->get(statusLinkFor('ada@example.com'))->assertOk()->assertDontSee('id="wk-referral-link"', false);
});

it('carries a referral link\'s code into the sign-up, and only a well-formed one', function (): void {
    liveCampaign();

    $this->get('/waitlist/launch?ref=abc123xyz')->assertSee('<input type="hidden" name="ref" value="abc123xyz">', false);
    $this->get('/waitlist/launch?ref=%3Cscript%3E')->assertDontSee('name="ref"', false);
});
