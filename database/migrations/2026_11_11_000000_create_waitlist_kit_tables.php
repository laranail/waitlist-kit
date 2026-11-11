<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Simtabi\Laranail\WaitlistKit\Support\Tables;

/**
 * Every table the kit needs, in one migration: campaigns, subscribers, blocks, funnel events and integrations.
 *
 * Polymorphic owners use `*able` columns holding morph-map aliases (`laranail-waitlist-kit.campaign`), never class
 * names, so renaming a class never strands a row. Index names are explicit and short, so any table prefix stays under
 * PostgreSQL's 63-character limit.
 */
return new class extends Migration
{
    public function getConnection(): ?string
    {
        return Tables::connection();
    }

    public function up(): void
    {
        $schema = Schema::connection($this->getConnection());

        $schema->create(Tables::name('campaigns'), static function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug', 120)->unique('wk_campaigns_slug_unique');
            $table->string('type', 32);
            $table->string('status', 32)->default('draft');
            $table->string('host')->nullable()->index('wk_campaigns_host_index');
            $table->json('locales');
            $table->string('theme')->nullable();
            $table->json('theme_settings');
            $table->json('settings');
            $table->timestamp('launches_at')->nullable();
            $table->timestamps();
        });

        $schema->create(Tables::name('subscribers'), static function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('campaign_id')->constrained(Tables::name('campaigns'), 'id', 'wk_subscribers_campaign_fk')->cascadeOnDelete();
            $table->string('email', 254);
            $table->string('status', 32)->default('pending');
            $table->string('locale', 16)->default('en');
            $table->json('fields');
            $table->unsignedInteger('position')->nullable();
            $table->string('referral_code', 32)->nullable()->unique('wk_subscribers_referral_code_unique');
            $table->ulid('referred_by_id')->nullable()->index('wk_subscribers_referred_by_index');
            $table->json('consent');
            $table->json('attribution');
            $table->timestamps();
            $table->softDeletes();

            // One row per address per campaign: a repeat sign-up is the same lead, never a second one.
            $table->unique(['campaign_id', 'email'], 'wk_subscribers_campaign_email_unique');
            $table->index(['campaign_id', 'status'], 'wk_subscribers_campaign_status_index');
        });

        $schema->create(Tables::name('blocks'), static function (Blueprint $table): void {
            $table->ulid('id')->primary();
            // The page the block belongs to: a campaign today, any page-owning model later.
            $table->ulidMorphs('blockable', 'wk_blocks_blockable_index');
            $table->string('type', 64);
            $table->unsignedInteger('position')->default(0);
            $table->json('content');
            $table->boolean('visible')->default(true);
            $table->timestamps();
        });

        $schema->create(Tables::name('events'), static function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('campaign_id')->constrained(Tables::name('campaigns'), 'id', 'wk_events_campaign_fk')->cascadeOnDelete();
            // What the event is about: the campaign (a view) or a subscriber (a sign-up, a confirmation).
            $table->ulidMorphs('eventable', 'wk_events_eventable_index');
            $table->string('type', 32);
            $table->json('data');
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['campaign_id', 'type', 'occurred_at'], 'wk_events_funnel_index');
        });

        $schema->create(Tables::name('integrations'), static function (Blueprint $table): void {
            $table->ulid('id')->primary();
            // What the integration serves: a campaign, or nothing for one every campaign uses.
            $table->nullableUlidMorphs('integrable', 'wk_integrations_integrable_index');
            $table->string('driver', 64);
            $table->text('settings')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->getConnection());

        foreach (['integrations', 'events', 'blocks', 'subscribers', 'campaigns'] as $table) {
            $schema->dropIfExists(Tables::name($table));
        }
    }
};
