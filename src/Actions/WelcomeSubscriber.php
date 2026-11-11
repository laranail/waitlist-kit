<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions;

use Throwable;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Mail\ThankYouMail;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Models\Integration;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Simtabi\Laranail\WaitlistKit\Jobs\MirrorSubscriber;

/**
 * What follows someone joining a list, once it is committed: the thank-you mail and the integrations. Moved verbatim
 * out of SubscribeToCampaign so that confirming a double opt-in can run the same step.
 */
final readonly class WelcomeSubscriber
{
    public function __construct(
        private KitConfig $config,
        private MailFactory $mail,
        private Dispatcher $bus,
        private LoggerInterface $log,
    ) {}

    /**
     * The mail and the integrations, once the sign-up is committed. Each is queued, but on the `sync` queue it runs
     * here, so a failure is caught and logged by id, never shown to the visitor: the address is already kept.
     */
    public function handle(Campaign $campaign, Subscriber $subscriber): void
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
