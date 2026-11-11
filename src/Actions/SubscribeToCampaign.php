<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions;

use Illuminate\Support\DateFactory;
use Simtabi\Laranail\WaitlistKit\Data\SignupData;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Data\SignupResult;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;
use Simtabi\Laranail\WaitlistKit\Exceptions\CampaignNotOpen;

/**
 * Puts an address on a campaign's list, once.
 *
 * The insert is `createOrFirst`, which runs in a savepoint: a repeat address hits the unique index, the savepoint rolls
 * back, and the existing row comes back. On PostgreSQL a failed insert outside a savepoint would abort the request's
 * whole transaction. Only a new subscriber is recorded in the funnel, thanked and sent to the integrations, and the
 * last two happen after the commit, so nothing is mailed or forwarded for a row that was rolled back. Without double
 * opt-in the new subscriber also joins the list in the same transaction (AdmitToList): confirmed, positioned, coded.
 */
final readonly class SubscribeToCampaign
{
    public function __construct(
        private DateFactory $date,
        private AdmitToList $admit,
        private WelcomeSubscriber $welcome,
    ) {}

    public function handle(Campaign $campaign, SignupData $data): SignupResult
    {
        if (! $campaign->acceptsSignups()) {
            throw CampaignNotOpen::for($campaign->slug);
        }

        $result = $campaign->getConnection()->transaction(function () use ($campaign, $data): SignupResult {
            $subscriber = Subscriber::query()->withTrashed()->createOrFirst(
                ['campaign_id' => $campaign->id, 'email' => $data->email],
                [
                    'locale'         => $data->locale,
                    'attribution'    => $data->attribution,
                    'referred_by_id' => $this->referrerId($campaign, $data),
                ],
            );

            if ($subscriber->wasRecentlyCreated) {
                $subscriber->events()->create(['campaign_id' => $campaign->id, 'type' => FunnelEventType::Signup, 'occurred_at' => $this->date->now()]);

                // Without double opt-in, signing up is the confirmation: the address joins the list at once.
                if (! $campaign->conversion()->doubleOptIn) {
                    $this->admit->handle($subscriber);
                }
            }

            return new SignupResult($subscriber, created: $subscriber->wasRecentlyCreated);
        });

        if ($result->created) {
            $this->welcome->handle($campaign, $result->subscriber);
        }

        return $result;
    }

    private function referrerId(Campaign $campaign, SignupData $data): ?string
    {
        if ($data->referralCode === null) {
            return null;
        }

        $referrer = Subscriber::query()
            ->where('campaign_id', $campaign->id)
            ->where('referral_code', $data->referralCode)
            ->first();

        return $referrer?->id;
    }
}
