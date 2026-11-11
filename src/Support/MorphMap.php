<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Support;

use Illuminate\Database\Eloquent\Model;
use Simtabi\Laranail\WaitlistKit\Models\Page;
use Simtabi\Laranail\WaitlistKit\Models\Admin;
use Simtabi\Laranail\WaitlistKit\Models\Block;
use Simtabi\Laranail\WaitlistKit\Models\Enquiry;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;
use Simtabi\Laranail\WaitlistKit\Models\Subscriber;
use Simtabi\Laranail\WaitlistKit\Models\FunnelEvent;
use Simtabi\Laranail\WaitlistKit\Models\Integration;

/**
 * The aliases the kit's models are stored under in every `*able_type` column.
 *
 * Registered (merged) into the application's morph map by the provider, so a row names
 * `laranail-waitlist-kit.campaign`, never a class: renaming or moving a class strands nothing. The aliases carry the
 * package name because the morph map is one flat registry the application owns.
 *
 * The kit does not call Relation::enforceMorphMap(): that is global and would refuse every application model the
 * application has not mapped. MorphMapTest enforces it for the kit's own models instead.
 */
final class MorphMap
{
    public const string PREFIX = 'laranail-waitlist-kit.';

    /** @var array<string, class-string<Model>> */
    public const array MAP = [
        self::PREFIX . 'campaign'    => Campaign::class,
        self::PREFIX . 'subscriber'  => Subscriber::class,
        self::PREFIX . 'enquiry'     => Enquiry::class,
        self::PREFIX . 'page'        => Page::class,
        self::PREFIX . 'block'       => Block::class,
        self::PREFIX . 'event'       => FunnelEvent::class,
        self::PREFIX . 'admin'       => Admin::class,
        self::PREFIX . 'integration' => Integration::class,
    ];
}
