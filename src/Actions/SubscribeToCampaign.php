<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions;

use Throwable;
use Psr\Log\LoggerInterface;
use Illuminate\Support\DateFactory;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Simtabi\Laranail\WaitlistKit\Data\SignupData;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Data\SignupResult;
use Simtabi\Laranail\WaitlistKit\Mail\ThankYouMail;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Models\Integration;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;
use Simtabi\Laranail\WaitlistKit\Jobs\MirrorSubscriber;
use Simtabi\Laranail\WaitlistKit\Exceptions\CampaignNotOpen;

/**
 * Puts an address on a campaign's list, once.
 *
 * The insert is `createOrFirst`, which runs in a savepoint: a repeat address hits the unique index, the savepoint rolls
 * back, and the existing row comes back. On PostgreSQL a failed insert outside a savepoint would abort the request's
 * whole transaction. Only a new subscriber is recorded in the funnel, thanked and sent to the integrations, and the
 * last two happen after the commit, so nothing is mailed or forwarded for a row that was rolled back.
 */
final readonly class SubscribeToCampaign
{
    public function __construct(
        private KitConfig $config,
        private MailFactory $mail,
        private Dispatcher $bus,
        private DateFactory $date,
        private LoggerInterface $log,
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
            }

            return new SignupResult($subscriber, created: $subscriber->wasRecentlyCreated);
        });

        if ($result->created) {
            $this->afterSignup($campaign, $result->subscriber);
        }

        return $result;
    }

    /**
     * The mail and the integrations, once the sign-up is committed. Each is queued, but on the `sync` queue it runs
     * here, so a failure is caught and logged by id, never shown to the visitor: the address is already kept.
     */
    private function afterSignup(Campaign $campaign, Subscriber $subscriber): void
    {
        if ($this->config->thanksNewSignups()) {
            $this->quietly('thank-you mail', $subscriber, fn () => $this->mail->mailer()->to($subscriber->email)
                ->locale($subscriber->locale)
                ->queue(new ThankYouMail($this->config->siteName(), $campaign->name, $this->config->sender())));
        }

        foreach ($this->integrations($campaign) as $integration) {
            $this->quietly('integration ' . $integration->driver, $subscriber, fn () => $this->bus->dispatch(new MirrorSubscriber($subscriber->id, $integration->id)));
        }
    }

    private function quietly(string $what, Subscriber $subscriber, callable $send): void
    {
        try {
            $send();
        } catch (Throwable $e) {
            $this->log->warning('laranail/waitlist-kit: a sign-up follow-up failed', [
                'what'       => $what,
                'subscriber' => $subscriber->id,
                'reason'     => $e->getMessage(),
            ]);
        }
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

    /** @return iterable<Integration> the campaign's enabled integrations, and the enabled ones every campaign uses */
    private function integrations(Campaign $campaign): iterable
    {
        return Integration::query()
            ->where('enabled', true)
            ->where(static function (Builder $query) use ($campaign): void {
                $query->whereNull('integrable_type')
                    ->orWhere(static fn (Builder $owned) => $owned
                        ->where('integrable_type', $campaign->getMorphClass())
                        ->where('integrable_id', $campaign->id));
            })
            ->get();
    }
}
