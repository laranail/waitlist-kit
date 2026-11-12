<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions;

use Throwable;
use Psr\Log\LoggerInterface;
use Illuminate\Support\DateFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Simtabi\Laranail\WaitlistKit\Models\Page;
use Simtabi\Laranail\WaitlistKit\Support\Actor;
use Simtabi\Laranail\WaitlistKit\Models\Enquiry;
use Simtabi\Laranail\WaitlistKit\Data\ContactData;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Mail\EnquiryNotice;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;
use Simtabi\Laranail\WaitlistKit\Events\EnquiryReceived;

/**
 * Keeps an enquiry (every one: an enquiry is a message, not a list entry) and, after the commit, mails it to the
 * configured inbox with the visitor as Reply-To. The consent record names the privacy notice as it stood, so what was
 * agreed to can be shown later. A mail that fails is logged by id; the enquiry is already kept.
 */
final readonly class SubmitEnquiry
{
    public function __construct(
        private KitConfig $config,
        private MailFactory $mail,
        private DateFactory $date,
        private LoggerInterface $log,
        private Dispatcher $events,
    ) {}

    public function handle(ContactData $data, ?Actor $actor = null): Enquiry
    {
        $enquiry = (new Enquiry)->getConnection()->transaction(function () use ($data): Enquiry {
            $now = $this->date->now();
            $privacy = Page::query()->where('slug', Page::PRIVACY)->first();

            $enquiry = Enquiry::query()->create([
                'campaign_id' => $data->campaignId,
                'name'        => $data->name,
                'email'       => $data->email,
                'company'     => $data->company,
                'topic'       => $data->topic,
                'message'     => $data->message,
                'locale'      => $data->locale,
                'consent'     => $data->consented
                    ? ['agreed_at' => $now->toIso8601String(), 'privacy_notice' => $privacy?->updated_at?->toIso8601String()]
                    : [],
            ]);

            if ($data->campaignId !== null) {
                $enquiry->events()->create(['campaign_id' => $data->campaignId, 'type' => FunnelEventType::Enquiry, 'occurred_at' => $now]);
            }

            return $enquiry;
        });

        $inbox = $this->config->contactInbox();
        if ($inbox !== null) {
            try {
                $this->mail->mailer()->to($inbox)->queue(new EnquiryNotice($enquiry->id, $this->config->siteName(), $this->config->sender()));
            } catch (Throwable $e) {
                $this->log->warning('laranail/waitlist-kit: an enquiry notice failed', ['enquiry' => $enquiry->id, 'reason' => $e->getMessage()]);
            }
        }

        $this->events->dispatch(new EnquiryReceived($enquiry, $actor ?? Actor::system()));

        return $enquiry;
    }
}
