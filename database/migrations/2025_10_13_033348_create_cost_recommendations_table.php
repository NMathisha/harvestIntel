<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cost_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farming_operation_id')->constrained()->onDelete('cascade');
            $table->foreignId('farming_cost_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->decimal('total_costs_before', 10, 2)->check('amount >= 0');
            $table->decimal('costs_added', 10, 2)->check('amount >= 0');
            $table->decimal('costs_after', 10, 2)->check('amount >= 0');
            $table->decimal('fixed_cost', 10, 2)->check('amount >= 0');
            $table->decimal('variable_costs', 10, 2)->check('amount >= 0');
            $table->decimal('total_costs', 10, 2)->check('amount >= 0');
            $table->decimal('cost_per_acre', 10, 2)->check('amount >= 0');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_recommendations');
    }
};
