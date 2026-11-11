<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Models;

use Override;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Simtabi\Laranail\WaitlistKit\Database\Factories\IntegrationFactory;

/**
 * A configured driver sign-ups are sent to (an application endpoint, a mailing service). It serves an `integrable`
 * (a campaign), or every campaign when it has none. `settings` is encrypted with the application key and hidden from
 * serialisation, because it holds credentials.
 *
 * @property string $id
 * @property ?string $integrable_type
 * @property ?string $integrable_id
 * @property string $driver
 * @property array<string, mixed> $settings
 * @property bool $enabled
 */
final class Integration extends Model
{
    /** @use HasFactory<IntegrationFactory> */
    use HasFactory;

    #[Override]
    protected $fillable = ['driver', 'settings', 'enabled'];

    #[Override]
    protected $hidden = ['settings'];

    #[Override]
    protected $attributes = [
        'enabled' => true,
    ];

    /** @return MorphTo<\Illuminate\Database\Eloquent\Model, $this> */
    public function integrable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): IntegrationFactory
    {
        return IntegrationFactory::new();
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
