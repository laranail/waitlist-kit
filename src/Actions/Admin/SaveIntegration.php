<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Actions\Admin;

use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Integration;
use Simtabi\Laranail\WaitlistKit\Integrations\BrevoDriver;

/**
 * Creates or saves an integration for one campaign, or every campaign when none is given. A secret left empty on
 * edit keeps the stored one.
 */
final readonly class SaveIntegration
{
    /** @param  array<string, mixed>  $data  validated by IntegrationRules */
    public function handle(?Integration $integration, array $data): Integration
    {
        $driver = $this->text($data['driver'] ?? null);
        $stored = $integration->settings ?? [];

        $settings = match ($driver) {
            BrevoDriver::NAME => [
                'api_key' => is_string($data['api_key'] ?? null) && $data['api_key'] !== '' ? $data['api_key'] : ($stored['api_key'] ?? ''),
                'list_id' => is_numeric($data['list_id'] ?? null) ? (int) $data['list_id'] : 0,
            ],
            default => ['endpoint' => $this->text($data['endpoint'] ?? null)],
        };

        $integration ??= new Integration;
        $integration->fill(['driver' => $driver, 'settings' => $settings, 'enabled' => (bool) ($data['enabled'] ?? false)]);

        $campaign = is_string($data['campaign'] ?? null) && $data['campaign'] !== ''
            ? Campaign::query()->where('slug', $data['campaign'])->first()
            : null;
        $integration->integrable_type = $campaign?->getMorphClass();
        $integration->integrable_id = $campaign?->id;
        $integration->save();

        return $integration;
    }

    private function text(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }
}
