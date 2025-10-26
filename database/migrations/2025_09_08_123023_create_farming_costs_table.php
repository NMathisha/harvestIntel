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
        Schema::create('farming_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farming_operation_id')->constrained()->onDelete('cascade');
            $table->foreignId('cost_category_id')->constrained()->onDelete('cascade');
            $table->string('description');
            $table->decimal('amount', 10, 2)->check('amount >= 0');
            $table->date('incurred_date');
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('unit', 50)->nullable();
            $table->decimal('unit_price', 10, 2)->nullable(); // ADD THIS LINE
            $table->json('external_factors')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['farming_operation_id', 'cost_category_id']);
            $table->index(['incurred_date']);
            $table->index(['amount']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farming_costs');
    }
};
