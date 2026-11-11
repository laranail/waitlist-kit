<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Actions\AdmitToList;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;

beforeEach(fn () => Mail::fake());

it('puts each new sign-up on the list in order, with a code of its own, when there is no double opt-in', function (): void {
    liveCampaign();

    foreach (['ada', 'ben', 'cy'] as $name) {
        $this->postJson('/waitlist/launch', ['email' => "{$name}@example.com"])->assertCreated();
    }
    $this->postJson('/waitlist/launch', ['email' => 'ada@example.com'])->assertCreated(); // a repeat keeps its place

    $list = Subscriber::query()->orderBy('position')->get();

    expect($list->pluck('email')->all())->toBe(['ada@example.com', 'ben@example.com', 'cy@example.com'])
        ->and($list->pluck('position')->all())->toBe([1, 2, 3])
        ->and($list->every(fn (Subscriber $s): bool => $s->status === SubscriberStatus::Confirmed))->toBeTrue()
        ->and($list->pluck('referral_code')->unique())->toHaveCount(3);

    foreach ($list as $subscriber) {
        expect($subscriber->referral_code)->toMatch('/^[' . AdmitToList::CODE_ALPHABET . ']{' . AdmitToList::CODE_LENGTH . '}$/');
    }
});

it('leaves a sign-up pending, with no place or code, while the campaign asks for confirmation', function (): void {
    $campaign = liveCampaign();
    $campaign->update(['settings' => ['conversion' => ['double_opt_in' => true]]]);

    $this->postJson('/waitlist/launch', ['email' => 'ada@example.com'])->assertCreated();

    $subscriber = Subscriber::query()->sole();
    expect($subscriber->status)->toBe(SubscriberStatus::Pending)
        ->and($subscriber->position)->toBeNull()
        ->and($subscriber->referral_code)->toBeNull();
});

it('keeps a place once given, and never hands out a removed subscriber\'s place again', function (): void {
    $campaign = liveCampaign();
    $first = app(AdmitToList::class)->handle(Subscriber::factory()->create(['campaign_id' => $campaign->id]));
    $code = $first->referral_code;

    expect(app(AdmitToList::class)->handle($first)->position)->toBe(1)
        ->and($first->referral_code)->toBe($code);

    $first->delete();
    $second = app(AdmitToList::class)->handle(Subscriber::factory()->create(['campaign_id' => $campaign->id]));

    expect($second->position)->toBe(2);
});
