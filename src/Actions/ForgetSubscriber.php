<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions;

use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Models\FunnelEvent;

/**
 * Erases an address for good: every subscriber row with it, soft-deleted ones included, and the funnel events about
 * them. Referrals they brought stay, with their link to the erased subscriber cleared.
 */
final readonly class ForgetSubscriber
{
    public function handle(string $email): int
    {
        $subscribers = Subscriber::query()->withTrashed()->where('email', mb_strtolower(trim($email)))->get();
        if ($subscribers->isEmpty()) {
            return 0;
        }

        $ids = $subscribers->modelKeys();
        $morph = (new Subscriber)->getMorphClass();

        (new Subscriber)->getConnection()->transaction(static function () use ($ids, $morph, $subscribers): void {
            FunnelEvent::query()->where('eventable_type', $morph)->whereIn('eventable_id', $ids)->delete();
            Subscriber::query()->withTrashed()->whereIn('referred_by_id', $ids)->update(['referred_by_id' => null]);
            $subscribers->each(static fn (Subscriber $subscriber): ?bool => $subscriber->forceDelete());
        });

        return count($ids);
    }
}
