<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Services;

use Illuminate\Routing\UrlGenerator;
use Simtabi\Laranail\WaitlistKit\Models\Admin;
use Simtabi\Laranail\WaitlistKit\Enums\AuthMode;
use Illuminate\Notifications\AnonymousNotifiable;
use Simtabi\Laranail\WaitlistKit\Support\KitConfig;
use Simtabi\Laranail\WaitlistKit\Notifications\Alert;
use Illuminate\Contracts\Notifications\Dispatcher as Notifications;

/**
 * Sends an alert to whoever should get it: the addresses in alerts.to, by mail, and (in bundled sign-in, with
 * alerts.admins on) every kit administrator. An install that wants Slack, SMS or its own users listens to the kit's
 * events instead, or sends these notifications to its own notifiables.
 */
final readonly class AlertDispatcher
{
    public function __construct(
        private KitConfig $config,
        private Notifications $notifications,
        private UrlGenerator $urls,
    ) {}

    public function send(Alert $alert): void
    {
        $recipients = $this->config->alertRecipients();
        if ($recipients !== []) {
            $this->notifications->send((new AnonymousNotifiable)->route('mail', $recipients), $alert);
        }

        if ($this->config->alertsAdmins() && $this->config->authMode() === AuthMode::Bundled) {
            $this->notifications->send(Admin::query()->get(), $alert);
        }
    }

    /** A page in the backend, when the backend is on; null in headless installs. */
    public function backendUrl(string $route, mixed $parameters = []): ?string
    {
        return $this->config->adminEnabled() ? $this->urls->route($route, $parameters) : null;
    }
}
