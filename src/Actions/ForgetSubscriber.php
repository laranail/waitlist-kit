<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions;

use Illuminate\Contracts\Events\Dispatcher;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Simtabi\Laranail\WaitlistKit\Models\Enquiry;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Models\FunnelEvent;
use Simtabi\Laranail\WaitlistKit\Events\SubscriberForgotten;

/**
 * Erases an address for good: every subscriber row with it (soft-deleted ones included), every enquiry it sent, and
 * the funnel events about them. Referrals they brought stay, with their link to the erased subscriber cleared.
 */
final readonly class ForgetSubscriber
{
    public function __construct(private Dispatcher $events) {}

    /** @return array{subscribers: int, enquiries: int} how many records went */
    public function handle(string $email, ?Actor $actor = null): array
    {
        $email = mb_strtolower(trim($email));
        $subscribers = Subscriber::query()->withTrashed()->where('email', $email)->get();
        $enquiries = Enquiry::query()->where('email', $email)->get();

        $ids = $subscribers->modelKeys();
        $enquiryIds = $enquiries->modelKeys();

        (new Subscriber)->getConnection()->transaction(static function () use ($ids, $enquiryIds, $subscribers): void {
            FunnelEvent::query()->where('eventable_type', (new Subscriber)->getMorphClass())->whereIn('eventable_id', $ids)->delete();
            FunnelEvent::query()->where('eventable_type', (new Enquiry)->getMorphClass())->whereIn('eventable_id', $enquiryIds)->delete();
            Subscriber::query()->withTrashed()->whereIn('referred_by_id', $ids)->update(['referred_by_id' => null]);
            $subscribers->each(static fn (Subscriber $subscriber): ?bool => $subscriber->forceDelete());
            Enquiry::query()->whereKey($enquiryIds)->delete();
        });

        $this->events->dispatch(new SubscriberForgotten($email, count($ids), count($enquiryIds), $actor ?? Actor::system()));

        return ['subscribers' => count($ids), 'enquiries' => count($enquiryIds)];
    }
}
