<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Simtabi\Laranail\WaitlistKit\Support\Tables;

return new class extends Migration
{
    public function getConnection(): ?string
    {
        return Tables::connection();
    }

    public function up(): void
    {
        Schema::connection($this->getConnection())->create(Tables::name('events'), static function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('campaign_id')->constrained(Tables::name('campaigns'), 'id', 'wk_events_campaign_fk')->cascadeOnDelete();
            $table->ulid('subscriber_id')->nullable()->index('wk_events_subscriber_index');
            $table->string('type', 32);
            $table->json('data');
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();

            $table->index(['campaign_id', 'type', 'occurred_at'], 'wk_events_funnel_index');
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists(Tables::name('events'));
    }
};
