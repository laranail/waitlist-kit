<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * What every kit alert shares: queued, by mail to the configured addresses (an on-demand route) and to any notifiable
 * the application passes, and as an array for the `database` channel. Each alert says only what happened and where to
 * look; a subscriber's address is never put in an alert, since alerts go to inboxes the kit does not control.
 */
abstract class Alert extends Notification implements ShouldQueue
{
    use Queueable;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        // An on-demand route only has the channels it was given; a model uses the channels it says it accepts.
        if ($notifiable instanceof AnonymousNotifiable) {
            return array_keys($notifiable->routes);
        }

        if (method_exists($notifiable, 'waitlistKitAlertChannels')) {
            $channels = $notifiable->waitlistKitAlertChannels();

            return is_array($channels) ? array_values(array_filter($channels, is_string(...))) : ['mail'];
        }

        return ['mail'];
    }

    abstract public function subject(): string;

    /** @return list<string> */
    abstract public function lines(): array;

    abstract public function url(): ?string;

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)->subject($this->subject());
        foreach ($this->lines() as $line) {
            $message->line($line);
        }
        $url = $this->url();

        return $url === null ? $message : $message->action(__('laranail/waitlist-kit::alerts.open'), $url);
    }

    /** @return array{subject: string, lines: list<string>, url: ?string} */
    public function toArray(object $notifiable): array
    {
        return ['subject' => $this->subject(), 'lines' => $this->lines(), 'url' => $this->url()];
    }
}
