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
        Schema::connection($this->getConnection())->create(Tables::name('subscribers'), static function (Blueprint $table): void {
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
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists(Tables::name('subscribers'));
    }
};
