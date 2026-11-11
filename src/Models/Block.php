<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Models;

use Override;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One piece of a campaign's page (hero, form, features, countdown, FAQ, ...), in order. `content` holds its fields
 * per locale: `{"en": {"title": "..."}, "es": {...}}`.
 *
 * @property string $id
 * @property string $campaign_id
 * @property string $type
 * @property int $position
 * @property array<string, array<string, mixed>> $content
 * @property bool $visible
 */
final class Block extends Model
{
    #[Override]
    protected $fillable = ['campaign_id', 'type', 'position', 'content', 'visible'];

    #[Override]
    protected $attributes = [
        'position' => 0,
        'content'  => '{}',
        'visible'  => true,
    ];

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * This block's fields in one locale, falling back key by key to another.
     *
     * @return array<string, mixed>
     */
    public function contentFor(string $locale, string $fallback = 'en'): array
    {
        $own = $this->content[$locale] ?? [];
        $base = $this->content[$fallback] ?? [];

        return [...$base, ...$own];
    }

    protected function baseTable(): string
    {
        return 'blocks';
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'content'  => 'array',
            'visible'  => 'boolean',
        ];
    }
}
