<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Models;

use Override;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Simtabi\Laranail\WaitlistKit\Database\Factories\BlockFactory;

/**
 * One piece of a page (hero, form, features, countdown, FAQ, ...), in order. It belongs to a `blockable`: a campaign
 * today. `content` holds its fields per locale: `{"en": {"title": "..."}, "es": {...}}`.
 *
 * @property string $id
 * @property string $blockable_type
 * @property string $blockable_id
 * @property string $type
 * @property int $position
 * @property array<string, array<string, mixed>> $content
 * @property bool $visible
 */
final class Block extends Model
{
    /** @use HasFactory<BlockFactory> */
    use HasFactory;

    #[Override]
    protected $fillable = ['type', 'position', 'content', 'visible'];

    #[Override]
    protected $attributes = [
        'position' => 0,
        'content'  => '{}',
        'visible'  => true,
    ];

    /** @return MorphTo<\Illuminate\Database\Eloquent\Model, $this> */
    public function blockable(): MorphTo
    {
        return $this->morphTo();
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

    protected static function newFactory(): BlockFactory
    {
        return BlockFactory::new();
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
