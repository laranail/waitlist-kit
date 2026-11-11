<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Jobs;

use Throwable;
use Psr\Log\LoggerInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Models\Integration;
use Simtabi\Laranail\WaitlistKit\Integrations\Drivers;

/**
 * Sends a new subscriber to one integration, on the queue, after the sign-up commits. A failure is retried, then
 * logged with the subscriber's id and never their address. The visitor was answered long before, so they never see it.
 */
final class MirrorSubscriber implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    public function __construct(public readonly string $subscriberId, public readonly string $integrationId)
    {
        $this->afterCommit();
    }

    public function handle(Drivers $drivers, LoggerInterface $log): void
    {
        $subscriber = Subscriber::query()->find($this->subscriberId);
        $integration = Integration::query()->find($this->integrationId);

        // Erased or switched off since the sign-up: there is nothing to send.
        if ($subscriber === null || $integration === null || ! $integration->enabled) {
            return;
        }

        try {
            $drivers->get($integration->driver)->push($subscriber, $integration->settings);
        } catch (Throwable $e) {
            if ($this->attempts() >= $this->tries) {
                $log->warning('laranail/waitlist-kit: an integration did not take a subscriber', [
                    'integration' => $integration->id,
                    'driver'      => $integration->driver,
                    'subscriber'  => $subscriber->id,
                    'reason'      => $e->getMessage(),
                ]);

                return;
            }

            throw $e;
        }
    }
}
