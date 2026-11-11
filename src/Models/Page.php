<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Models;

use Override;
use Illuminate\Support\Carbon;
use Simtabi\Laranail\WaitlistKit\Enums\PageKind;
use Simtabi\Laranail\WaitlistKit\Enums\PageStatus;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Simtabi\Laranail\WaitlistKit\Database\Factories\PageFactory;

/**
 * A page the kit serves at /{prefix}/pages/{slug}: the privacy notice and terms the forms link to, or any other.
 * Its content is its blocks, the same blocks a campaign page uses, so the backend edits both the same way.
 *
 * @property string $id
 * @property string $slug
 * @property PageKind $kind
 * @property PageStatus $status
 * @property array<string, string> $title
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    public const string PRIVACY = 'privacy';

    public const string TERMS = 'terms';

    #[Override]
    protected $fillable = ['slug', 'kind', 'status', 'title'];

    #[Override]
    protected $attributes = [
        'status' => 'draft',
        'title'  => '{}',
    ];

    /** @return MorphMany<Block, $this> */
    public function blocks(): MorphMany
    {
        return $this->morphMany(Block::class, 'blockable')->orderBy('position');
    }

    public function isPublished(): bool
    {
        return $this->status === PageStatus::Published;
    }

    public function titleFor(string $locale, string $fallback = 'en'): string
    {
        return $this->title[$locale] ?? $this->title[$fallback] ?? $this->slug;
    }

    protected static function newFactory(): PageFactory
    {
        return PageFactory::new();
    }

    protected function baseTable(): string
    {
        return 'pages';
    }

    protected function casts(): array
    {
        return [
            'kind'   => PageKind::class,
            'status' => PageStatus::class,
            'title'  => 'array',
        ];
    }
}
