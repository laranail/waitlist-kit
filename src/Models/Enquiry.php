<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Models;

use Override;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Simtabi\Laranail\WaitlistKit\Database\Factories\EnquiryFactory;

/**
 * A message sent through the contact form, from a campaign page or any page of the application. Every enquiry is kept, unlike a list, where an address
 * appears once; the site's inbox gets a copy with the visitor as Reply-To.
 *
 * @property string $id
 * @property ?string $campaign_id
 * @property string $name
 * @property string $email
 * @property ?string $company
 * @property string $topic
 * @property string $message
 * @property string $locale
 * @property array<string, mixed> $consent
 */
final class Enquiry extends Model
{
    /** @use HasFactory<EnquiryFactory> */
    use HasFactory;

    #[Override]
    protected $fillable = ['campaign_id', 'name', 'email', 'company', 'topic', 'message', 'locale', 'consent'];

    #[Override]
    protected $attributes = [
        'locale'  => 'en',
        'consent' => '{}',
    ];

    /** @return BelongsTo<Campaign, $this> */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /** @return MorphMany<FunnelEvent, $this> */
    public function events(): MorphMany
    {
        return $this->morphMany(FunnelEvent::class, 'eventable');
    }

    protected static function newFactory(): EnquiryFactory
    {
        return EnquiryFactory::new();
    }

    protected function baseTable(): string
    {
        return 'enquiries';
    }

    protected function casts(): array
    {
        return [
            'consent' => 'array',
        ];
    }
}
