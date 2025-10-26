<?php

// ============================================
// FILE: database/factories/FarmingOperationFactory.php
// ============================================

namespace Database\Factories;

use App\Models\FarmingOperation;
use Illuminate\Database\Eloquent\Factories\Factory;

class FarmingOperationFactory extends Factory
{
    protected $model = FarmingOperation::class;

    public function definition(): array
    {
        $seasonStart = $this->faker->dateTimeBetween('-6 months', '+1 month');
        $seasonEnd = (clone $seasonStart)->modify('+' . rand(90, 240) . ' days');

        return [
            'name' => $this->faker->words(3, true) . ' Operation',
            'type' => $this->faker->randomElement(['crops', 'livestock', 'mixed']),
            'total_acres' => $this->faker->randomFloat(2, 10, 500),
            'season_start' => $seasonStart,
            'season_end' => $seasonEnd,
            'expected_yield' => $this->faker->randomFloat(2, 100, 5000),
            'yield_unit' => $this->faker->randomElement(['bushels', 'kg', 'tons', 'lbs']),
            'commodity_price' => $this->faker->randomFloat(2, 5, 100),
            'location' => $this->faker->randomElement([
                'Western Province, Sri Lanka',
                'Central Province, Sri Lanka',
                'Southern Province, Sri Lanka',
                'Northern Province, Sri Lanka',
                'Eastern Province, Sri Lanka'
            ]),
            'weather_data' => [
                'avg_temperature' => $this->faker->randomFloat(1, 20, 35),
                'total_rainfall' => $this->faker->numberBetween(800, 2500),
                'frost_days' => $this->faker->numberBetween(0, 15),
                'humidity_avg' => $this->faker->randomFloat(1, 60, 90),
                'wind_speed_avg' => $this->faker->randomFloat(1, 5, 25)
            ]
        ];
    }

    /**
     * Indicate that the operation is active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'season_start' => now()->subDays(10),
            'season_end' => now()->addDays(20)
        ]);
    }

    /**
     * Indicate that the operation is completed
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'season_start' => now()->subDays(120),
            'season_end' => now()->subDays(10)
        ]);
    }

    /**
     * Indicate that the operation is planned (future)
     */
    public function planned(): static
    {
        return $this->state(fn (array $attributes) => [
            'season_start' => now()->addDays(30),
            'season_end' => now()->addDays(150)
        ]);
    }

    /**
     * Set specific operation type
     */
    public function crops(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'crops',
            'yield_unit' => 'bushels'
        ]);
    }

    public function livestock(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'livestock',
            'yield_unit' => 'head'
        ]);
    }

    public function mixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'mixed'
        ]);
    }
}
