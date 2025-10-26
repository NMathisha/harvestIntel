<?php

namespace Database\Factories;

use App\Models\CostPrediction;
use App\Models\FarmingOperation;
use App\Models\CostCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class CostPredictionFactory extends Factory
{
    protected $model = CostPrediction::class;

    public function definition(): array
    {
        $predictedAmount = $this->faker->randomFloat(2, 500, 25000);
        $actualAmount = $this->faker->boolean(70) ? $this->faker->randomFloat(2, $predictedAmount * 0.8, $predictedAmount * 1.2) : null;
        $predictionError = $actualAmount ? abs($predictedAmount - $actualAmount) / $actualAmount : null;

        return [
            'farming_operation_id' => FarmingOperation::factory(),
            'cost_category_id' => CostCategory::factory(),
            'predicted_amount' => $predictedAmount,
            'confidence_score' => $this->faker->randomFloat(4, 0.5, 0.95),
            'prediction_factors' => [
                'acres' => $this->faker->randomFloat(2, 10, 500),
                'season_length' => $this->faker->numberBetween(60, 240),
                'avg_temperature' => $this->faker->randomFloat(1, 20, 35),
                'total_rainfall' => $this->faker->numberBetween(800, 2500)
            ],
            'model_used' => $this->faker->randomElement(['Ridge', 'RegressionTree', 'GradientBoost', 'Fallback']),
            'prediction_date' => now(),
            'target_date' => $this->faker->dateTimeBetween('now', '+6 months'),
            'actual_amount' => $actualAmount,
            'prediction_error' => $predictionError
        ];
    }

    /**
     * Indicate that the prediction has actual amount
     */
    public function withActual(): static
    {
        return $this->state(function (array $attributes) {
            $actualAmount = $this->faker->randomFloat(2, $attributes['predicted_amount'] * 0.8, $attributes['predicted_amount'] * 1.2);
            return [
                'actual_amount' => $actualAmount,
                'prediction_error' => abs($attributes['predicted_amount'] - $actualAmount) / $actualAmount
            ];
        });
    }

    /**
     * Indicate that the prediction is accurate (low error)
     */
    public function accurate(): static
    {
        return $this->state(function (array $attributes) {
            $actualAmount = $this->faker->randomFloat(2, $attributes['predicted_amount'] * 0.95, $attributes['predicted_amount'] * 1.05);
            return [
                'actual_amount' => $actualAmount,
                'prediction_error' => abs($attributes['predicted_amount'] - $actualAmount) / $actualAmount,
                'confidence_score' => $this->faker->randomFloat(4, 0.85, 0.95)
            ];
        });
    }
}
