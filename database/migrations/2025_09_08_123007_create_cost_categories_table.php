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
        Schema::create('cost_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Ensure unique category names
            $table->enum('type', ['fixed', 'variable']);
            $table->text('description')->nullable(); // Changed to text for longer descriptions
            $table->boolean('is_predictable')->default(true);
            $table->decimal('typical_percentage', 5, 2)->nullable()->comment('Typical % of total costs');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_predictable']); // Index for filtering
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_categories');
    }
};
