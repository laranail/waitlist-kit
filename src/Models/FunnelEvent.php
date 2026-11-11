<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Models;

use Override;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Simtabi\Laranail\WaitlistKit\Enums\FunnelEventType;
use Simtabi\Laranail\WaitlistKit\Database\Factories\FunnelEventFactory;

/**
 * One funnel step, append-only: never updated, so the dashboard's history cannot be rewritten. `campaign_id` keeps
 * the funnel queryable per campaign; `eventable` is what the step is about (the campaign for a view, a subscriber for
 * a sign-up).
 *
 * @property string $id
 * @property string $campaign_id
 * @property string $eventable_type
 * @property string $eventable_id
 * @property FunnelEventType $type
 * @property array<string, mixed> $data
 * @property CarbonImmutable $occurred_at
 */
final class FunnelEvent extends Model
{
    /** @use HasFactory<FunnelEventFactory> */
    use HasFactory;

    public const null UPDATED_AT = null;

    #[Override]
    protected $fillable = ['campaign_id', 'type', 'data', 'occurred_at'];

    #[Override]
    protected $attributes = [
        'data' => '{}',
    ];

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** @return MorphTo<\Illuminate\Database\Eloquent\Model, $this> */
    public function eventable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): FunnelEventFactory
    {
        return FunnelEventFactory::new();
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
