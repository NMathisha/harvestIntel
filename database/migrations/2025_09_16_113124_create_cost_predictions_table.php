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
        Schema::create('cost_predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farming_operation_id')->constrained()->onDelete('cascade');
            $table->foreignId('cost_category_id')->constrained()->onDelete('cascade');
            $table->decimal('predicted_amount', 10, 2);
            $table->decimal('confidence_score', 5, 4)->check('confidence_score >= 0 AND confidence_score <= 1');
            $table->json('prediction_factors');
            $table->string('model_used', 100);
            $table->timestamp('prediction_date');
            $table->date('target_date');
            $table->decimal('actual_amount', 10, 2)->nullable();
            $table->decimal('prediction_error', 5, 4)->nullable()->comment('Absolute percentage error');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance and accuracy tracking
            $table->index(['farming_operation_id', 'cost_category_id']);
            $table->index(['prediction_date']);
            $table->index(['actual_amount']); // For accuracy analysis

            // Unique constraint to prevent duplicate predictions
            $table->unique(['farming_operation_id', 'cost_category_id', 'prediction_date'], 'unique_prediction');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_predictions');
    }
};
