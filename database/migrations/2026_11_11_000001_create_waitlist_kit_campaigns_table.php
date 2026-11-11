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
        Schema::connection($this->getConnection())->create(Tables::name('campaigns'), static function (Blueprint $table): void {
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
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists(Tables::name('campaigns'));
    }
};
