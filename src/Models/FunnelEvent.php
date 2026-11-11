<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Models;

use Override;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;

/**
 * One funnel step, append-only: never updated, so the dashboard's history cannot be rewritten.
 *
 * @property string $id
 * @property string $campaign_id
 * @property ?string $subscriber_id
 * @property FunnelEventType $type
 * @property array<string, mixed> $data
 * @property CarbonImmutable $occurred_at
 */
final class FunnelEvent extends Model
{
    public const UPDATED_AT = null;

    #[Override]
    protected $fillable = ['campaign_id', 'subscriber_id', 'type', 'data', 'occurred_at'];

    #[Override]
    protected $attributes = [
        'data' => '{}',
    ];

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** @return BelongsTo<Subscriber, $this> */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class);
    }

    protected function baseTable(): string
    {
        return 'events';
    }

    protected function casts(): array
    {
        return [
            'type'        => FunnelEventType::class,
            'data'        => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
