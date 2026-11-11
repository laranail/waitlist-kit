<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions;

use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;

/**
 * Puts a subscriber on the list: confirmed, the next position, and a referral code of their own. Runs at sign-up
 * when the campaign has no double opt-in, and on confirmation when it has. Idempotent: someone already on the list
 * keeps their place. Someone who joined through a referral link credits their referrer (CreditReferral).
 *
 * Positions are handed out under a lock on the campaign's row, so two people joining at once never share a place.
 */
final readonly class AdmitToList
{
    /** Lower-case letters and digits, without the look-alikes 0, o, 1, l and i, so a code read aloud survives. */
    public const string CODE_ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    public const int CODE_LENGTH = 10;

    public function __construct(private CreditReferral $credit) {}

    public function handle(Subscriber $subscriber): Subscriber
    {
        if ($subscriber->position !== null) {
            return $subscriber;
        }

        return $subscriber->getConnection()->transaction(function () use ($subscriber): Subscriber {
            $campaign = Campaign::query()->whereKey($subscriber->campaign_id)->lockForUpdate()->firstOrFail();

            $last = Subscriber::query()->withTrashed()->where('campaign_id', $subscriber->campaign_id)->max('position');

            $subscriber->forceFill([
                'status'        => SubscriberStatus::Confirmed,
                'position'      => (is_numeric($last) ? (int) $last : 0) + 1,
                'referral_code' => $subscriber->referral_code ?? $this->uniqueCode(),
            ])->save();

            // Joining through someone's link moves them up, under the same lock.
            $this->credit->handle($campaign, $subscriber);

            return $subscriber;
        });
    }

    /** A code no subscriber holds, from the CSPRNG; with 31^10 codes a retry is all but never needed. */
    private function uniqueCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
        } while (Subscriber::query()->withTrashed()->where('referral_code', $code)->exists());

        return $code;
    }
}
