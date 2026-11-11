<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions;

use Throwable;
use Psr\Log\LoggerInterface;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\DateFactory;
use Illuminate\Contracts\Routing\UrlGenerator;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Mail\ConfirmMail;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Support\MailLinks;
use Illuminate\Contracts\Mail\Factory as MailFactory;

/**
 * Mails a pending subscriber the signed link that confirms them (double opt-in). At most once per RESEND_MINUTES per
 * subscriber, so signing up again re-sends a lost link without letting the form be used to flood an inbox. A failure
 * is logged by id and never shown: the sign-up is already kept, and signing up again tries once more.
 */
final readonly class AskToConfirm
{
    /** How long a confirmation link works. */
    public const int LINK_DAYS = 7;

    public const int RESEND_MINUTES = 15;

    public function __construct(
        private KitConfig $config,
        private MailFactory $mail,
        private UrlGenerator $urls,
        private DateFactory $date,
        private RateLimiter $limiter,
        private LoggerInterface $log,
        private MailLinks $links,
    ) {}

    public function handle(Campaign $campaign, Subscriber $subscriber): bool
    {
        $key = 'laranail-waitlist-kit.confirm:' . $subscriber->id;
        if ($this->limiter->tooManyAttempts($key, 1)) {
            return false;
        }
        $this->limiter->hit($key, self::RESEND_MINUTES * 60);

        $link = $this->urls->temporarySignedRoute(
            'laranail-waitlist-kit.campaign.confirm',
            $this->date->now()->addDays(self::LINK_DAYS),
            ['campaign' => $campaign->slug, 'subscriber' => $subscriber->id],
        );

        try {
            $this->mail->mailer()->to($subscriber->email)
                ->locale($subscriber->locale)
                ->queue(new ConfirmMail($this->config->siteName(), $campaign->name, $link, self::LINK_DAYS, $this->config->sender(), $this->links->unsubscribe($campaign, $subscriber)));
        } catch (Throwable $e) {
            $this->log->warning('laranail/waitlist-kit: a confirmation mail failed', ['subscriber' => $subscriber->id, 'reason' => $e->getMessage()]);
        }

        return true;
    }
}
