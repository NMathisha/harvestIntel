<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateFarmingOperationsTable extends Migration
{
    public function up(): void
    {
        Schema::create('farming_operations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->enum('type', ['crops', 'livestock', 'mixed']);
            $table->decimal('total_acres', 10, 2);
            $table->date('season_start');
            $table->date('season_end');
            $table->decimal('expected_yield', 10, 2)->nullable();
            $table->string('yield_unit', 50)->nullable();
            $table->json('weather_data')->nullable();
            $table->decimal('commodity_price', 8, 2)->nullable();
            $table->string('location')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Composite index for common queries
            $table->index(['type', 'season_start']);
        });

        // Add check constraints using raw SQL (database-specific)
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'pgsql') {
            DB::statement('ALTER TABLE farming_operations ADD CONSTRAINT check_total_acres CHECK (total_acres > 0)');
            DB::statement('ALTER TABLE farming_operations ADD CONSTRAINT check_season_dates CHECK (season_end > season_start)');
        } elseif ($driver === 'sqlite') {
            // SQLite requires recreating the table with constraints
            // For development, you might skip this or handle differently
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('farming_operations');
    }
}
