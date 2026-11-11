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
        Schema::connection($this->getConnection())->create(Tables::name('blocks'), static function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('campaign_id')->constrained(Tables::name('campaigns'), 'id', 'wk_blocks_campaign_fk')->cascadeOnDelete();
            $table->string('type', 64);
            $table->unsignedInteger('position')->default(0);
            $table->json('content');
            $table->boolean('visible')->default(true);
            $table->timestamps();

            $table->index(['campaign_id', 'position'], 'wk_blocks_campaign_position_index');
        });
    }

    public function down(): void
    {
        Schema::connection($this->getConnection())->dropIfExists(Tables::name('blocks'));
    }
};
