<?php

namespace Database\Factories;

use App\Models\FarmingCost;
use App\Models\CostCategory;
use App\Models\FarmingOperation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FarmingCost>
 */
class FarmingCostFactory extends Factory
{
    protected $model = FarmingCost::class;

    public function definition(): array
    {
        $amount = $this->faker->randomFloat(2, 100, 50000);
        $quantity = $this->faker->randomFloat(2, 1, 500);

        return [
            'farming_operation_id' => FarmingOperation::factory(),
            'cost_category_id' => CostCategory::factory(),
            'description' => $this->faker->sentence(),
            'amount' => $amount,
            'incurred_date' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'quantity' => $quantity,
            'unit' => $this->faker->randomElement(['acres', 'kg', 'lbs', 'hours', 'units']),
            'unit_price' => round($amount / $quantity, 4),
            'external_factors' => [
                'fuel_price' => $this->faker->randomFloat(2, 2, 5),
                'labor_rate' => $this->faker->randomFloat(2, 10, 25),
                'input_price_index' => $this->faker->numberBetween(90, 120)
            ]
        ];
    }

    /**
     * Set specific operation
     */
    public function forOperation(FarmingOperation $operation): static
    {
        return $this->state(fn (array $attributes) => [
            'farming_operation_id' => $operation->id,
            'incurred_date' => $this->faker->dateTimeBetween(
                $operation->season_start,
                $operation->season_end
            )
        ]);
    }

    /**
     * Set specific category
     */
    public function forCategory(CostCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'cost_category_id' => $category->id
        ]);
    }

    /**
     * Set specific amount
     */
    public function withAmount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount
        ]);
    }
}
