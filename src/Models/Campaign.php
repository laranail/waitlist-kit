<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Models;

use Override;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignType;
use Simtabi\Laranail\WaitlistKit\Enums\CampaignStatus;

/**
 * One waitlist, coming-soon or early-access page. Create through CreateCampaign, which holds the install to its
 * configured number of campaigns.
 *
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property CampaignType $type
 * @property CampaignStatus $status
 * @property ?string $host
 * @property list<string> $locales
 * @property ?string $theme
 * @property array<string, mixed> $theme_settings
 * @property array<string, mixed> $settings
 * @property ?Carbon $launches_at
 */
final class Campaign extends Model
{
    #[Override]
    protected $fillable = ['name', 'slug', 'type', 'status', 'host', 'locales', 'theme', 'theme_settings', 'settings', 'launches_at'];

    #[Override]
    protected $attributes = [
        'status'         => 'draft',
        'locales'        => '["en"]',
        'theme_settings' => '{}',
        'settings'       => '{}',
    ];

    /** @return HasMany<Subscriber, $this> */
    public function subscribers(): HasMany
    {
        return $this->hasMany(Subscriber::class);
    }

    /** @return HasMany<Block, $this> */
    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class)->orderBy('position');
    }

    /** @return HasMany<FunnelEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(FunnelEvent::class);
    }

    public function acceptsSignups(): bool
    {
        return $this->status === CampaignStatus::Live;
    }

    protected function baseTable(): string
    {
        return 'campaigns';
    }

    protected function casts(): array
    {
        return [
            'type'           => CampaignType::class,
            'status'         => CampaignStatus::class,
            'locales'        => 'array',
            'theme_settings' => 'array',
            'settings'       => 'array',
            'launches_at'    => 'immutable_datetime',
        ];
    }
}
