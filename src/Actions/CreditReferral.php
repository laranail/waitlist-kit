<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions;

use Illuminate\Support\DateFactory;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Models\FunnelEvent;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;

/**
 * Credits a referrer when someone they referred joins the list: the referrer moves up the campaign's bump, never past
 * first place, and everyone they pass moves down one, so places stay unique. Each credit is a `referral` event on the
 * referrer, and the cap counts those events. Nothing happens with referrals off, a bump of 0, the cap reached, or a
 * referrer who is the same person, not on the list yet, or has left it.
 *
 * Runs inside AdmitToList's transaction, under its lock on the campaign row.
 */
final readonly class CreditReferral
{
    public function __construct(private DateFactory $date) {}

    /** Whether the referrer was credited. */
    public function handle(Campaign $campaign, Subscriber $referred): bool
    {
        $rules = $campaign->conversion();
        if (! $rules->referrals || $rules->referralBump === 0 || $referred->referred_by_id === null || $referred->referred_by_id === $referred->id) {
            return false;
        }

        $referrer = Subscriber::query()
            ->where('campaign_id', $campaign->id)
            ->whereKey($referred->referred_by_id)
            ->whereNotNull('position')
            ->where('status', '!=', SubscriberStatus::Unsubscribed)
            ->first();
        if ($referrer === null || $referrer->email === $referred->email) {
            return false;
        }

        $credited = FunnelEvent::query()
            ->where('eventable_type', $referrer->getMorphClass())
            ->where('eventable_id', $referrer->id)
            ->where('type', FunnelEventType::Referral)
            ->count();
        if ($credited >= $rules->referralCap) {
            return false;
        }

        $from = (int) $referrer->position;
        $to = max(1, $from - $rules->referralBump);
        if ($to < $from) {
            Subscriber::query()->withTrashed()
                ->where('campaign_id', $campaign->id)
                ->whereBetween('position', [$to, $from - 1])
                ->increment('position');
            $referrer->forceFill(['position' => $to])->save();
        }

        $referrer->events()->create([
            'campaign_id' => $campaign->id,
            'type'        => FunnelEventType::Referral,
            'data'        => ['referred' => $referred->id, 'from' => $from, 'to' => $to],
            'occurred_at' => $this->date->now(),
        ]);

        return true;
    }
}
