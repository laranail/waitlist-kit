<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Integrations;

use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\ConnectionException;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Exceptions\IntegrationFailed;

/**
 * A Brevo contact list. `updateEnabled` makes an address already on the list a success, so a retry is harmless.
 * Settings: `api_key`, `list_id`.
 */
final readonly class BrevoDriver implements Driver
{
    public const string NAME = 'brevo';

    public const string ENDPOINT = 'https://api.brevo.com/v3/contacts';

    public function __construct(private Http $http) {}

    public function push(Subscriber $subscriber, array $settings): void
    {
        $key = $settings['api_key'] ?? null;
        $list = $settings['list_id'] ?? null;
        if (! is_string($key) || $key === '' || ! is_int($list) || $list < 1) {
            throw IntegrationFailed::because(self::NAME, 'settings.api_key and settings.list_id are not both set');
        }

        try {
            $response = $this->http->acceptJson()->timeout(10)->withHeaders(['api-key' => $key])->post(self::ENDPOINT, [
                'email'         => $subscriber->email,
                'listIds'       => [$list],
                'updateEnabled' => true,
                'attributes'    => ['LOCALE' => $subscriber->locale],
            ]);
        } catch (ConnectionException) {
            throw IntegrationFailed::because(self::NAME, 'Brevo could not be reached');
        }

        if (! $response->successful()) {
            throw IntegrationFailed::because(self::NAME, 'answered ' . $response->status());
        }
    }
}
