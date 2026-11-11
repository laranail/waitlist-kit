<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions;

use Throwable;
use Psr\Log\LoggerInterface;
use Illuminate\Support\DateFactory;
use Simtabi\Laranail\WaitlistKit\Mail\InviteMail;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Support\MailLinks;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;
use Simtabi\Laranail\WaitlistKit\Exceptions\NoInviteUrl;

/**
 * Invites the next $count confirmed subscribers, in list order: each becomes invited, gets an invite event, and is
 * mailed a link to the campaign's invite URL carrying their opaque code (`?invite=`), which the product hands back to
 * the conversion endpoint. Only confirmed subscribers are invited, so running it twice never invites anyone twice.
 */
final readonly class InviteSubscribers
{
    public const int MAX_BATCH = 500;

    public function __construct(
        private KitConfig $config,
        private MailFactory $mail,
        private MailLinks $links,
        private DateFactory $date,
        private LoggerInterface $log,
    ) {}

    /** How many were invited. */
    public function handle(Campaign $campaign, int $count): int
    {
        $inviteUrl = $campaign->conversion()->inviteUrl ?? throw NoInviteUrl::for($campaign->slug);
        $count = max(0, min($count, self::MAX_BATCH));

        $invited = $campaign->getConnection()->transaction(function () use ($campaign, $count) {
            $next = Subscriber::query()
                ->where('campaign_id', $campaign->id)
                ->where('status', SubscriberStatus::Confirmed)
                ->whereNotNull('position')
                ->orderBy('position')
                ->limit($count)
                ->lockForUpdate()
                ->get();

            foreach ($next as $subscriber) {
                $subscriber->forceFill(['status' => SubscriberStatus::Invited])->save();
                $subscriber->events()->create(['campaign_id' => $campaign->id, 'type' => FunnelEventType::Invite, 'occurred_at' => $this->date->now()]);
            }

            return $next;
        });

        foreach ($invited as $subscriber) {
            $link = $inviteUrl . (str_contains($inviteUrl, '?') ? '&' : '?') . http_build_query(['invite' => $subscriber->referral_code], '', '&', PHP_QUERY_RFC3986);
            try {
                $this->mail->mailer()->to($subscriber->email)->locale($subscriber->locale)
                    ->queue(new InviteMail($this->config->siteName(), $campaign->name, $link, $this->config->sender(), $this->links->unsubscribe($campaign, $subscriber)));
            } catch (Throwable $e) {
                $this->log->warning('laranail/waitlist-kit: an invitation mail failed', ['subscriber' => $subscriber->id, 'reason' => $e->getMessage()]);
            }
        }

        return $invited->count();
    }
}
