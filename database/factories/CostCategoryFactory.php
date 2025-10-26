<?php

namespace Database\Factories;

use App\Models\CostCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CostCategory>
 */
class CostCategoryFactory extends Factory
{
    protected $model = CostCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'type' => $this->faker->randomElement(['fixed', 'variable']),
            'description' => $this->faker->sentence(),
            'is_predictable' => $this->faker->boolean(80), // 80% predictable
            'typical_percentage' => $this->faker->randomFloat(2, 2, 25)
        ];
    }

    /**
     * Indicate that the category is fixed cost
     */
    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fixed'
        ]);
    }

    /**
     * Indicate that the category is variable cost
     */
    public function variable(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'variable'
        ]);
    }

    /**
     * Indicate that the category is predictable
     */
    public function predictable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_predictable' => true
        ]);
    }

    /**
     * Indicate that the category is not predictable
     */
    public function notPredictable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_predictable' => false
        ]);
    }
}
