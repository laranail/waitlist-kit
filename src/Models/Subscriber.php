<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Models;

use Override;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Simtabi\Laranail\WaitlistKit\Enums\SubscriberStatus;

/**
 * A lead in one campaign. The address is unique per campaign. Erasure soft-deletes first, so the funnel's counts
 * survive until the purge.
 *
 * @property string $id
 * @property string $campaign_id
 * @property string $email
 * @property SubscriberStatus $status
 * @property string $locale
 * @property array<string, mixed> $fields
 * @property ?int $position
 * @property ?string $referral_code
 * @property ?string $referred_by_id
 * @property array<string, mixed> $consent
 * @property array<string, string> $attribution
 */
final class Subscriber extends Model
{
    use SoftDeletes;

    #[Override]
    protected $fillable = ['campaign_id', 'email', 'status', 'locale', 'fields', 'position', 'referral_code', 'referred_by_id', 'consent', 'attribution'];

    #[Override]
    protected $attributes = [
        'status'      => 'pending',
        'locale'      => 'en',
        'fields'      => '{}',
        'consent'     => '{}',
        'attribution' => '{}',
    ];

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** @return BelongsTo<Subscriber, $this> */
    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_id');
    }

    /** @return HasMany<Subscriber, $this> */
    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by_id');
    }

    protected function baseTable(): string
    {
        return 'subscribers';
    }

    protected function casts(): array
    {
        return [
            'status'      => SubscriberStatus::class,
            'fields'      => 'array',
            'position'    => 'integer',
            'consent'     => 'array',
            'attribution' => 'array',
        ];
    }
}
