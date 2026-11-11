<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Integrations;

use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\ConnectionException;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Exceptions\IntegrationFailed;

/**
 * An application's own list: `{"email", "locale", "campaign"}` posted as JSON to `settings.endpoint`, server to server.
 * Any 2xx is a success, so the endpoint should answer an address it already has with a 2xx too.
 */
final readonly class AppDriver implements Driver
{
    public const string NAME = 'app';

    public function __construct(private Http $http) {}

    public function push(Subscriber $subscriber, array $settings): void
    {
        $endpoint = $settings['endpoint'] ?? null;
        if (! is_string($endpoint) || ! str_starts_with($endpoint, 'https://')) {
            throw IntegrationFailed::because(self::NAME, 'settings.endpoint must be an https:// URL');
        }

        try {
            $response = $this->http->acceptJson()->timeout(10)->post($endpoint, [
                'email'    => $subscriber->email,
                'locale'   => $subscriber->locale,
                'campaign' => $subscriber->campaign()->value('slug'),
            ]);
        } catch (ConnectionException) {
            throw IntegrationFailed::because(self::NAME, 'the endpoint could not be reached');
        }

        if (! $response->successful()) {
            throw IntegrationFailed::because(self::NAME, 'answered ' . $response->status());
        }
    }
}
