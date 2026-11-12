<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Simtabi\Laranail\WaitlistKit\WaitlistKit;
use Simtabi\Laranail\WaitlistKit\Models\Admin;
use Illuminate\Notifications\AnonymousNotifiable;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Actions\Unsubscribe;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;
use Simtabi\Laranail\WaitlistKit\Events\CampaignDeleted;
use Simtabi\Laranail\WaitlistKit\Events\EnquiryReceived;
use Simtabi\Laranail\WaitlistKit\Events\ReferralCredited;
use Simtabi\Laranail\WaitlistKit\Policies\CampaignPolicy;
use Simtabi\Laranail\WaitlistKit\Actions\ForgetSubscriber;
use Simtabi\Laranail\WaitlistKit\Actions\ConfirmSubscriber;
use Simtabi\Laranail\WaitlistKit\Actions\ConvertSubscriber;
use Simtabi\Laranail\WaitlistKit\Events\SubscriberSignedUp;
use Simtabi\Laranail\WaitlistKit\Events\SubscribersInvited;
use Simtabi\Laranail\WaitlistKit\Notifications\SignupAlert;
use Simtabi\Laranail\WaitlistKit\Events\SubscriberConfirmed;
use Simtabi\Laranail\WaitlistKit\Events\SubscriberConverted;
use Simtabi\Laranail\WaitlistKit\Events\SubscriberForgotten;
use Simtabi\Laranail\WaitlistKit\Notifications\EnquiryAlert;
use Simtabi\Laranail\WaitlistKit\Notifications\MilestoneAlert;
use Simtabi\Laranail\WaitlistKit\Events\SubscriberUnsubscribed;
use Simtabi\Laranail\WaitlistKit\Events\CampaignMilestoneReached;
use Simtabi\Laranail\WaitlistKit\Providers\WaitlistKitServiceProvider;

beforeEach(fn () => Mail::fake());

it('raises an event for every change to a subscriber', function (): void {
    Event::fake([SubscriberSignedUp::class, SubscriberConfirmed::class, SubscriberUnsubscribed::class, SubscriberConverted::class, SubscribersInvited::class, SubscriberForgotten::class, ReferralCredited::class]);
    $campaign = liveCampaign();
    $campaign->update(['settings' => ['conversion' => ['double_opt_in' => true, 'invite_url' => 'https://app.example.com/join', 'referrals' => true]]]);
    $kit = app(WaitlistKit::class);

    $dana = $kit->signup('launch')->email('dana@example.com')->subscribe()->subscriber;
    Event::assertDispatched(SubscriberSignedUp::class);
    Event::assertNotDispatched(SubscriberConfirmed::class); // pending until the link

    app(ConfirmSubscriber::class)->handle($campaign, $dana);
    Event::assertDispatched(SubscriberConfirmed::class, fn (SubscriberConfirmed $e): bool => $e->subscriber->is($dana));

    $kai = $kit->signup('launch')->email('kai@example.com')->referredBy($dana->fresh()?->referral_code)->subscribe()->subscriber;
    app(ConfirmSubscriber::class)->handle($campaign, $kai);
    Event::assertDispatched(ReferralCredited::class, fn (ReferralCredited $e): bool => $e->referrer->is($dana) && $e->referred->is($kai));

    $kit->invite('launch')->top(1)->send();
    Event::assertDispatched(SubscribersInvited::class, fn (SubscribersInvited $e): bool => $e->subscribers->count() === 1);

    app(ConvertSubscriber::class)->handle($dana->fresh() ?? $dana);
    Event::assertDispatched(SubscriberConverted::class);

    app(Unsubscribe::class)->handle($kai->fresh() ?? $kai);
    Event::assertDispatched(SubscriberUnsubscribed::class);

    app(ForgetSubscriber::class)->handle('kai@example.com');
    Event::assertDispatched(SubscriberForgotten::class, fn (SubscriberForgotten $e): bool => $e->email === 'kai@example.com' && $e->subscribers === 1);
});

it('raises events for enquiries and deleted campaigns, after the commit', function (): void {
    Event::fake([EnquiryReceived::class, CampaignDeleted::class]);
    config()->set('laranail.waitlist-kit.contact.consent', false);
    $kit = app(WaitlistKit::class);
    liveCampaign();

    $kit->enquiry()->from('Dana Lee', 'dana@example.com')->topic('sales')->message('Do you have a plan for teams?')->submit();
    $kit->campaign('launch')->delete();

    Event::assertDispatched(EnquiryReceived::class);
    Event::assertDispatched(CampaignDeleted::class, fn (CampaignDeleted $e): bool => $e->slug === 'launch');
});

it('sends no alert unless alerts are on', function (): void {
    Notification::fake();
    config()->set('laranail.waitlist-kit.alerts.to', ['team@example.org']);
    liveCampaign();

    app(WaitlistKit::class)->signup('launch')->email('dana@example.com')->subscribe();

    Notification::assertNothingSent();
});

it('alerts the team to sign-ups and enquiries, without the sender\'s address', function (): void {
    Notification::fake();
    config()->set('laranail.waitlist-kit.alerts', ['enabled' => true, 'to' => ['team@example.org'], 'on' => ['signup' => true, 'enquiry' => true, 'milestone' => true], 'milestones' => []]);
    config()->set('laranail.waitlist-kit.contact.consent', false);
    liveCampaign();
    $kit = app(WaitlistKit::class);

    $kit->signup('launch')->email('dana@example.com')->subscribe();
    $kit->enquiry()->from('Dana Lee', 'dana@example.com')->topic('sales')->message('Do you have a plan for teams?')->submit();

    Notification::assertSentOnDemand(SignupAlert::class, function (SignupAlert $alert, array $channels, AnonymousNotifiable $to): bool {
        $mail = $alert->toMail($to);

        return $to->routes['mail'] === ['team@example.org'] && $alert->listSize === 1
            && ! str_contains(implode(' ', $mail->introLines) . $mail->subject, 'dana@example.com');
    });
    Notification::assertSentOnDemand(EnquiryAlert::class, fn (EnquiryAlert $alert): bool => $alert->topic === 'sales');
});

it('alerts the kit\'s administrators in bundled sign-in when asked to', function (): void {
    Notification::fake();
    config()->set('laranail.waitlist-kit.auth.mode', 'bundled');
    config()->set('laranail.waitlist-kit.alerts', ['enabled' => true, 'to' => [], 'admins' => true, 'on' => ['signup' => true], 'milestones' => []]);
    $admin = Admin::factory()->create();
    liveCampaign();

    app(WaitlistKit::class)->signup('launch')->email('dana@example.com')->subscribe();

    Notification::assertSentTo($admin, SignupAlert::class);
});

it('raises each milestone once per campaign, and only the largest when several pass at once', function (): void {
    Event::fake([CampaignMilestoneReached::class]);
    config()->set('laranail.waitlist-kit.alerts.milestones', [2, 3]);
    $campaign = liveCampaign();
    Subscriber::factory()->for($campaign)->count(3)->sequence(fn ($s): array => ['position' => $s->index + 1, 'status' => SubscriberStatus::Confirmed])->create();
    $kit = app(WaitlistKit::class);

    // The list is already at 3: the first confirmation passes 2 and 3 together and announces 3 only.
    $kit->signup('launch')->email('dana@example.com')->subscribe();
    $kit->signup('launch')->email('kai@example.com')->subscribe();

    Event::assertDispatchedTimes(CampaignMilestoneReached::class, 1);
    Event::assertDispatched(CampaignMilestoneReached::class, fn (CampaignMilestoneReached $e): bool => $e->size === 3);
});

it('alerts on a milestone', function (): void {
    Notification::fake();
    config()->set('laranail.waitlist-kit.alerts', ['enabled' => true, 'to' => ['team@example.org'], 'on' => ['milestone' => true], 'milestones' => [1]]);
    liveCampaign();

    app(WaitlistKit::class)->signup('launch')->email('dana@example.com')->subscribe();

    Notification::assertSentOnDemand(MilestoneAlert::class, fn (MilestoneAlert $alert): bool => $alert->size === 1);
});

it('registers its policies, and keeps an application\'s own', function (): void {
    expect(Gate::getPolicyFor(Campaign::class))->toBeInstanceOf(CampaignPolicy::class)
        ->and(array_keys(WaitlistKitServiceProvider::POLICIES))->each->toBeString();

    $own = new class
    {
        public function update(): bool
        {
            return true;
        }
    };
    Gate::policy(Campaign::class, $own::class);
    // A second boot (another provider, a test refresh) must not put the kit's back.
    new WaitlistKitServiceProvider(app())->packageBooted();

    expect(Gate::getPolicyFor(Campaign::class))->toBeInstanceOf($own::class);
});
