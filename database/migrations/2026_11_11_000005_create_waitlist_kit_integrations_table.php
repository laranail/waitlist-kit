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
        Schema::connection($this->getConnection())->create(Tables::name('integrations'), static function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('campaign_id')->nullable()->constrained(Tables::name('campaigns'), 'id', 'wk_integrations_campaign_fk')->cascadeOnDelete();
            $table->string('driver', 64);
            $table->text('settings')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists(Tables::name('integrations'));
    }
};
