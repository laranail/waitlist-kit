<?php

declare(strict_types=1);

namespace Simtabi\Laranail\WaitlistKit\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Simtabi\Laranail\WaitlistKit\Support\Tables;
use Simtabi\Laranail\WaitlistKit\Tests\TestCase;
use Simtabi\Laranail\WaitlistKit\Models\Campaign;

final class CustomPrefixTest extends TestCase
{
    protected function tearDown(): void
    {
        Tables::configure('laranail_waitlist_kit_', null);
        parent::tearDown();
    }

    public function test_a_custom_prefix_names_every_table(): void
    {
        $this->assertSame('site_waitlist_campaigns', (new Campaign)->getTable());
        $this->assertTrue(Schema::hasTable('site_waitlist_subscribers'));
        $this->assertFalse(Schema::hasTable('laranail_waitlist_kit_subscribers'));
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app->make('config')->set('laranail.waitlist-kit.database.table_prefix', 'site_waitlist_');
    }
}
