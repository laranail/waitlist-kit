<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Simtabi\Laranail\WaitlistKit\Mail\ConfirmMail;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Models\FunnelEvent;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;

beforeEach(fn () => Mail::fake());

/** Signs each address up to `launch`, the last ones through $ref when given. */
function referralJoin(array $emails, ?string $ref = null): void
{
    foreach ($emails as $email) {
        test()->postJson('/waitlist/launch', array_filter(['email' => $email, 'ref' => $ref]))->assertCreated();
    }
}

/** @return array<string, int> each address's place, in order */
function referralPlaces(): array
{
    return Subscriber::query()->whereNotNull('position')->orderBy('position')->pluck('position', 'email')->map(fn ($p): int => (int) $p)->all();
}

function referralCodeOf(string $email): string
{
    return (string) Subscriber::query()->where('email', $email)->value('referral_code');
}

it('moves the referrer up by the bump, and everyone passed down one, keeping places unique', function (): void {
    liveCampaign();
    referralJoin(['a@example.com', 'b@example.com', 'c@example.com', 'd@example.com']);

    referralJoin(['e@example.com'], referralCodeOf('d@example.com'));

    expect(referralPlaces())->toBe(['a@example.com' => 1, 'b@example.com' => 2, 'd@example.com' => 3, 'c@example.com' => 4, 'e@example.com' => 5])
        ->and(FunnelEvent::query()->where('type', FunnelEventType::Referral)->sole()->data)->toMatchArray(['from' => 4, 'to' => 3]);
});

it('never moves anyone past first place', function (): void {
    liveCampaign()->update(['settings' => ['conversion' => ['referral_bump' => 5]]]);
    referralJoin(['a@example.com', 'b@example.com']);

    referralJoin(['c@example.com'], referralCodeOf('b@example.com'));

    expect(referralPlaces())->toBe(['b@example.com' => 1, 'a@example.com' => 2, 'c@example.com' => 3]);
});

it('stops crediting at the cap', function (): void {
    liveCampaign()->update(['settings' => ['conversion' => ['referral_cap' => 2]]]);
    referralJoin(['a@example.com', 'b@example.com', 'c@example.com', 'd@example.com', 'e@example.com']);

    referralJoin(['f@example.com', 'g@example.com', 'h@example.com'], referralCodeOf('e@example.com'));

    expect(referralPlaces()['e@example.com'])->toBe(3)
        ->and(FunnelEvent::query()->where('type', FunnelEventType::Referral)->count())->toBe(2)
        ->and(array_values(referralPlaces()))->toBe(range(1, 8));
});

it('credits nobody with referrals off, a bump of 0, or a referrer who has left', function (array $conversion, bool $left): void {
    liveCampaign()->update(['settings' => ['conversion' => $conversion]]);
    referralJoin(['a@example.com', 'b@example.com']);
    if ($left) {
        Subscriber::query()->where('email', 'b@example.com')->update(['status' => SubscriberStatus::Unsubscribed]);
    }

    referralJoin(['c@example.com'], referralCodeOf('b@example.com'));

    expect(referralPlaces())->toBe(['a@example.com' => 1, 'b@example.com' => 2, 'c@example.com' => 3])
        ->and(FunnelEvent::query()->where('type', FunnelEventType::Referral)->count())->toBe(0);
})->with([
    'referrals off' => [['referrals' => false], false],
    'bump of 0'     => [['referral_bump' => 0], false],
    'referrer left' => [[], true],
]);

it('credits a referral only once the referred person confirms, under double opt-in', function (): void {
    liveCampaign()->update(['settings' => ['conversion' => ['double_opt_in' => false]]]);
    referralJoin(['a@example.com', 'b@example.com']);
    Subscriber::query()->first()->campaign->update(['settings' => ['conversion' => ['double_opt_in' => true]]]);

    referralJoin(['c@example.com'], referralCodeOf('b@example.com'));
    expect(referralPlaces())->toBe(['a@example.com' => 1, 'b@example.com' => 2]);

    $link = '';
    Mail::assertQueued(ConfirmMail::class, function (ConfirmMail $mail) use (&$link): bool {
        $link = $mail->link;

        return true;
    });
    $this->get($link);

    expect(referralPlaces())->toBe(['b@example.com' => 1, 'a@example.com' => 2, 'c@example.com' => 3]);
});
