<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Models;

use Override;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A configured driver a campaign's sign-ups are sent to (a webhook, a mailing service, an application endpoint).
 * `settings` is encrypted with the application key, because it holds credentials.
 *
 * @property string $id
 * @property ?string $campaign_id
 * @property string $driver
 * @property array<string, mixed> $settings
 * @property bool $enabled
 */
final class Integration extends Model
{
    #[Override]
    protected $fillable = ['campaign_id', 'driver', 'settings', 'enabled'];

    #[Override]
    protected $hidden = ['settings'];

    #[Override]
    protected $attributes = [
        'enabled' => true,
    ];

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    protected function baseTable(): string
    {
        return 'integrations';
    }

    protected function casts(): array
    {
        return [
            'settings' => 'encrypted:array',
            'enabled'  => 'boolean',
        ];
    }
}
