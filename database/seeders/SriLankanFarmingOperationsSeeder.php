<?php

namespace Database\Seeders;

use App\Models\FarmingOperation;
use App\Models\CostCategory;
use App\Models\FarmingCost;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class SriLankanFarmingOperationsSeeder extends Seeder
{
    private array $sriLankanProvinces = [
        'Western' => ['multiplier' => 1.25, 'main_crops' => ['vegetables', 'rice']],
        'Central' => ['multiplier' => 1.15, 'main_crops' => ['tea', 'vegetables']],
        'Southern' => ['multiplier' => 1.05, 'main_crops' => ['rice', 'coconut', 'cinnamon']],
        'Northern' => ['multiplier' => 0.85, 'main_crops' => ['rice', 'vegetables', 'fruits']],
        'Eastern' => ['multiplier' => 0.90, 'main_crops' => ['rice', 'vegetables']],
        'North Western' => ['multiplier' => 0.95, 'main_crops' => ['coconut', 'rice']],
        'North Central' => ['multiplier' => 0.88, 'main_crops' => ['rice']],
        'Uva' => ['multiplier' => 1.10, 'main_crops' => ['tea', 'vegetables']],
        'Sabaragamuwa' => ['multiplier' => 1.08, 'main_crops' => ['tea', 'rubber', 'rice']]
    ];

    private array $cropData = [
        'rice' => [
            'yield_per_acre' => 150, // bushels per acre (converted from MT/ha)
            'market_price' => 65,    // LKR per kg
            'season_months' => 4,
            'water_intensive' => true
        ],
        'tea' => [
            'yield_per_acre' => 1200, // kg per acre
            'market_price' => 800,     // LKR per kg (made tea)
            'season_months' => 12,     // Perennial
            'water_intensive' => false
        ],
        'coconut' => [
            'yield_per_acre' => 8000,  // nuts per acre per year
            'market_price' => 35,       // LKR per nut
            'season_months' => 12,      // Perennial
            'water_intensive' => false
        ],
        'vegetables' => [
            'yield_per_acre' => 8000,  // kg per acre
            'market_price' => 120,      // LKR per kg (average)
            'season_months' => 3,
            'water_intensive' => true
        ],
        'rubber' => [
            'yield_per_acre' => 1500,  // kg per acre per year
            'market_price' => 450,      // LKR per kg
            'season_months' => 12,      // Perennial
            'water_intensive' => false
        ],
        'cinnamon' => [
            'yield_per_acre' => 200,   // kg per acre
            'market_price' => 1200,    // LKR per kg
            'season_months' => 12,     // Perennial
            'water_intensive' => false
        ],
        'fruits' => [
            'yield_per_acre' => 6000,  // kg per acre
            'market_price' => 150,      // LKR per kg (average)
            'season_months' => 6,
            'water_intensive' => true
        ]
    ];

    public function run(): void
    {
        // Ensure cost categories exist
        $this->call(CostCategorySriLankaSeeder::class);

        $categories = CostCategory::all();
        $currentYear = Carbon::now()->year;

        // Create 5 years of historical data (2020-2024)
        for ($year = 2020; $year <= 2024; $year++) {
            $this->createYearlyOperations($year, $categories);
        }

        // Create current year operations (2025)
        $this->createCurrentYearOperations($currentYear + 1, $categories);
    }

    private function createYearlyOperations(int $year, $categories): void
    {
        $operationsCreated = 0;

        foreach ($this->sriLankanProvinces as $province => $provinceData) {
            foreach ($provinceData['main_crops'] as $crop) {
                // Create 2-3 operations per crop per province per year
                $operationCount = rand(2, 3);

                for ($i = 1; $i <= $operationCount; $i++) {
                    $operation = $this->createFarmOperation($year, $province, $crop, $i);
                    $this->addCostsToOperation($operation, $categories, $provinceData['multiplier']);
                    $operationsCreated++;
                }
            }
        }

        $this->command->info("Created {$operationsCreated} operations for year {$year}");
    }

    private function createFarmOperation(int $year, string $province, string $crop, int $sequence): FarmingOperation
    {
        $cropInfo = $this->cropData[$crop];
        $acres = $this->generateRealisticAcreage($crop);

        // Calculate season dates based on Sri Lankan agricultural calendar
        [$seasonStart, $seasonEnd] = $this->getSeasonDates($year, $crop);

        // Calculate weather data based on Sri Lankan climate patterns
        $weatherData = $this->generateSriLankanWeatherData($province, $year, $seasonStart, $seasonEnd);

        // Generate market prices with Sri Lankan inflation and volatility
        $marketPrice = $this->calculateMarketPrice($crop, $year, $province);

        return FarmingOperation::create([
            'name' => "{$year} {$province} {$crop} Operation {$sequence}",
            'type' => $this->getCropType($crop),
            'total_acres' => $acres,
            'season_start' => $seasonStart,
            'season_end' => $seasonEnd,
            'expected_yield' => $acres * $cropInfo['yield_per_acre'] * (0.8 + rand(0, 40) / 100),
            'yield_unit' => $this->getYieldUnit($crop),
            'commodity_price' => $marketPrice,
            'location' => $province . " Province, Sri Lanka",
            'weather_data' => $weatherData
        ]);
    }

    private function generateRealisticAcreage(string $crop): float
    {
        // Sri Lankan farm sizes vary by crop type
        $acreageRanges = [
            'rice' => [1.5, 8.0],      // Small to medium paddy fields
            'tea' => [0.5, 15.0],      // Small holders to estates
            'coconut' => [1.0, 25.0],  // Small to large plantations
            'vegetables' => [0.25, 3.0], // Market gardens
            'rubber' => [2.0, 50.0],   // Small to large estates
            'cinnamon' => [0.5, 5.0],  // Small spice gardens
            'fruits' => [0.5, 4.0]     // Home gardens to orchards
        ];

        [$min, $max] = $acreageRanges[$crop] ?? [1.0, 5.0];
        return round(($min + (rand(0, 100) / 100) * ($max - $min)), 2);
    }

    private function getSeasonDates(int $year, string $crop): array
    {
        // Sri Lankan cropping seasons
        $seasonPatterns = [
            'rice' => [
                'maha' => ['start' => [10, 1], 'end' => [2, 28]], // Oct-Feb (main season)
                'yala' => ['start' => [4, 15], 'end' => [8, 31]]   // Apr-Aug (dry season)
            ],
            'vegetables' => [
                'year_round' => ['start' => [1, 1], 'end' => [12, 31]]
            ],
            'tea' => [
                'perennial' => ['start' => [1, 1], 'end' => [12, 31]]
            ],
            'coconut' => [
                'perennial' => ['start' => [1, 1], 'end' => [12, 31]]
            ],
            'rubber' => [
                'perennial' => ['start' => [1, 1], 'end' => [12, 31]]
            ],
            'cinnamon' => [
                'harvest_cycle' => ['start' => [1, 1], 'end' => [12, 31]]
            ],
            'fruits' => [
                'seasonal' => ['start' => [3, 1], 'end' => [9, 30]]
            ]
        ];

        if ($crop === 'rice') {
            $season = rand(0, 1) ? 'maha' : 'yala';
            $pattern = $seasonPatterns['rice'][$season];
        } else {
            $patterns = $seasonPatterns[$crop] ?? $seasonPatterns['vegetables'];
            $pattern = array_values($patterns)[0];
        }

        $startDate = Carbon::create($year, $pattern['start'][0], $pattern['start'][1]);
        $endDate = Carbon::create($year, $pattern['end'][0], $pattern['end'][1]);

        // Adjust for year boundary crossing
        if ($endDate->lessThan($startDate)) {
            $endDate->addYear();
        }

        return [$startDate, $endDate];
    }

    private function generateSriLankanWeatherData(string $province, int $year, Carbon $start, Carbon $end): array
    {
        // Sri Lankan climate data by province
        $climateData = [
            'Western' => ['temp' => [26, 31], 'rainfall' => [2000, 2500], 'humidity' => [75, 85]],
            'Central' => ['temp' => [18, 24], 'rainfall' => [1500, 2000], 'humidity' => [70, 80]],
            'Southern' => ['temp' => [25, 30], 'rainfall' => [1800, 2200], 'humidity' => [75, 85]],
            'Northern' => ['temp' => [27, 35], 'rainfall' => [800, 1200], 'humidity' => [65, 75]],
            'Eastern' => ['temp' => [26, 32], 'rainfall' => [1200, 1800], 'humidity' => [70, 80]],
            'North Western' => ['temp' => [26, 32], 'rainfall' => [1000, 1400], 'humidity' => [70, 80]],
            'North Central' => ['temp' => [26, 34], 'rainfall' => [1000, 1500], 'humidity' => [65, 75]],
            'Uva' => ['temp' => [20, 26], 'rainfall' => [1200, 1800], 'humidity' => [70, 80]],
            'Sabaragamuwa' => ['temp' => [22, 28], 'rainfall' => [1600, 2200], 'humidity' => [75, 85]]
        ];

        $climate = $climateData[$province];

        // Add yearly variations and climate change trends
        $yearlyVariation = ($year - 2020) * 0.2; // Gradual warming
        $randomVariation = (rand(-20, 20) / 10); // ±2°C variation

        return [
            'avg_temperature' => round($climate['temp'][0] +
                (($climate['temp'][1] - $climate['temp'][0]) * rand(0, 100) / 100) +
                $yearlyVariation + $randomVariation, 1),
            'total_rainfall' => round($climate['rainfall'][0] +
                (($climate['rainfall'][1] - $climate['rainfall'][0]) * rand(0, 100) / 100)),
            'humidity_avg' => round($climate['humidity'][0] +
                (($climate['humidity'][1] - $climate['humidity'][0]) * rand(0, 100) / 100), 1),
            'monsoon_months' => $this->getMonsoonMonths($province),
            'dry_spell_days' => rand(10, 45),
            'extreme_weather_events' => rand(0, 3),
            'sunshine_hours' => rand(1800, 2800),
            'wind_speed_avg' => round(rand(80, 150) / 10, 1), // km/h
        ];
    }

    private function getMonsoonMonths(string $province): array
    {
        // Sri Lankan monsoon patterns
        $monsoonPatterns = [
            'Western' => ['southwest' => [5, 6, 7, 8, 9], 'northeast' => [10, 11, 12, 1]],
            'Central' => ['southwest' => [5, 6, 7, 8], 'northeast' => [10, 11, 12]],
            'Southern' => ['southwest' => [5, 6, 7, 8, 9], 'northeast' => [11, 12, 1]],
            'Northern' => ['northeast' => [10, 11, 12, 1, 2]],
            'Eastern' => ['northeast' => [10, 11, 12, 1, 2], 'southwest' => [5, 6]],
            'North Western' => ['southwest' => [5, 6, 7, 8], 'northeast' => [11, 12, 1]],
            'North Central' => ['northeast' => [10, 11, 12, 1], 'southwest' => [5, 6]],
            'Uva' => ['northeast' => [10, 11, 12, 1]],
            'Sabaragamuwa' => ['southwest' => [5, 6, 7, 8], 'northeast' => [10, 11, 12]]
        ];

        return $monsoonPatterns[$province] ?? ['southwest' => [5, 6, 7, 8], 'northeast' => [10, 11, 12]];
    }

    private function calculateMarketPrice(string $crop, int $year, string $province): float
    {
        $basePrice = $this->cropData[$crop]['market_price'];

        // Sri Lankan inflation rates (approximate)
        $inflationRates = [
            2020 => 4.6,
            2021 => 6.0,
            2022 => 25.2, // High inflation year
            2023 => 15.8,
            2024 => 8.5,
            2025 => 6.0
        ];

        // Apply cumulative inflation from 2020
        $inflatedPrice = $basePrice;
        for ($y = 2020; $y <= $year; $y++) {
            $inflatedPrice *= (1 + ($inflationRates[$y] ?? 5.0) / 100);
        }

        // Regional price variations
        $regionalMultipliers = [
            'Western' => 1.15,     // Higher prices near Colombo
            'Central' => 1.05,     // Tea premium
            'Southern' => 1.02,
            'Northern' => 0.92,    // Remote areas
            'Eastern' => 0.88,
            'North Western' => 0.95,
            'North Central' => 0.90,
            'Uva' => 0.98,
            'Sabaragamuwa' => 1.00
        ];

        $regionalPrice = $inflatedPrice * ($regionalMultipliers[$province] ?? 1.0);

        // Add market volatility (±20%)
        $volatility = 0.8 + (rand(0, 40) / 100);

        return round($regionalPrice * $volatility, 2);
    }

    private function addCostsToOperation(FarmingOperation $operation, $categories, float $provinceMultiplier): void
    {
        // Sri Lankan cost structure by crop type
        $costStructures = [
            'rice' => [
                'Seeds/Seedlings' => 8000,        // LKR per acre
                'Fertilizers' => 25000,           // Higher fertilizer costs
                'Pesticides/Fungicides' => 8000,
                'Fuel/Diesel' => 12000,
                'Seasonal Labor' => 35000,        // High labor costs
                'Irrigation/Water' => 6000,
                'Transportation' => 4000,
                'Processing/Storage' => 8000,
                'Land Rent/Lease' => 15000,
                'Equipment/Machinery' => 10000,
                'Insurance' => 2000,
                'Property Taxes/Rates' => 1500,
                'Permanent Labor' => 60000
            ],
            'tea' => [
                'Seeds/Seedlings' => 5000,        // Bushes/replanting
                'Fertilizers' => 35000,
                'Pesticides/Fungicides' => 12000,
                'Fuel/Diesel' => 8000,
                'Seasonal Labor' => 80000,        // Very labor intensive
                'Irrigation/Water' => 3000,
                'Transportation' => 6000,
                'Processing/Storage' => 15000,    // Tea processing
                'Land Rent/Lease' => 25000,
                'Equipment/Machinery' => 20000,
                'Insurance' => 4000,
                'Property Taxes/Rates' => 3000,
                'Permanent Labor' => 120000
            ],
            'coconut' => [
                'Seeds/Seedlings' => 3000,
                'Fertilizers' => 15000,
                'Pesticides/Fungicides' => 5000,
                'Fuel/Diesel' => 6000,
                'Seasonal Labor' => 25000,
                'Irrigation/Water' => 2000,
                'Transportation' => 3000,
                'Processing/Storage' => 5000,
                'Land Rent/Lease' => 20000,
                'Equipment/Machinery' => 8000,
                'Insurance' => 2500,
                'Property Taxes/Rates' => 2000,
                'Permanent Labor' => 40000
            ],
            'vegetables' => [
                'Seeds/Seedlings' => 15000,
                'Fertilizers' => 30000,
                'Pesticides/Fungicides' => 18000,
                'Fuel/Diesel' => 10000,
                'Seasonal Labor' => 45000,
                'Irrigation/Water' => 12000,
                'Transportation' => 8000,
                'Processing/Storage' => 3000,
                'Land Rent/Lease' => 18000,
                'Equipment/Machinery' => 12000,
                'Insurance' => 3000,
                'Property Taxes/Rates' => 1800,
                'Permanent Labor' => 50000
            ]
        ];

        // Get base costs for the crop type
        $cropType = $this->determineCropCategory($operation->name);
        $baseCosts = $costStructures[$cropType] ?? $costStructures['vegetables'];

        foreach ($categories as $category) {
            $baseCost = $baseCosts[$category->name] ?? 0;

            if ($baseCost <= 0) continue;

            // Calculate final cost with all adjustments
            $finalCost = $baseCost * $operation->total_acres * $provinceMultiplier;

            // Add seasonal variations
            $seasonalMultiplier = $this->getSeasonalMultiplier($category->name, $operation->season_start);
            $finalCost *= $seasonalMultiplier;

            // Add random variation (±15%)
            $finalCost *= (0.85 + (rand(0, 30) / 100));

            // Create the cost record
            FarmingCost::create([
                'farming_operation_id' => $operation->id,
                'cost_category_id' => $category->id,
                'description' => $this->generateCostDescription($category->name, $operation),
                'amount' => round($finalCost, 2),
                'incurred_date' => $this->calculateIncurredDate($operation, $category),
                'quantity' => $category->type === 'variable' ? $operation->total_acres : null,
                'unit' => $category->type === 'variable' ? 'acres' : null,
                'unit_price' => $category->type === 'variable' ? round($finalCost / $operation->total_acres, 2) : null,
                'external_factors' => $this->generateExternalFactors($operation, $category)
            ]);
        }
    }

    private function determineCropCategory(string $operationName): string
    {
        $name = strtolower($operationName);

        if (str_contains($name, 'rice') || str_contains($name, 'paddy')) return 'rice';
        if (str_contains($name, 'tea')) return 'tea';
        if (str_contains($name, 'coconut')) return 'coconut';
        if (str_contains($name, 'vegetable')) return 'vegetables';
        if (str_contains($name, 'rubber')) return 'coconut'; // Similar cost structure
        if (str_contains($name, 'cinnamon') || str_contains($name, 'spice')) return 'coconut';
        if (str_contains($name, 'fruit')) return 'vegetables'; // Similar cost structure

        return 'vegetables'; // Default
    }

    private function getSeasonalMultiplier(string $categoryName, Carbon $seasonStart): float
    {
        $month = $seasonStart->month;

        // Seasonal cost variations in Sri Lanka
        $seasonalFactors = [
            'Seeds/Seedlings' => [
                'maha' => 1.1,  // Oct-Feb (higher demand)
                'yala' => 0.95, // Apr-Aug
                'default' => 1.0
            ],
            'Fertilizers' => [
                'maha' => 1.15, // Higher prices during main season
                'yala' => 1.05,
                'default' => 1.0
            ],
            'Seasonal Labor' => [
                'harvest' => 1.3, // Peak harvesting periods
                'planting' => 1.2,
                'default' => 1.0
            ],
            'Fuel/Diesel' => [
                'peak' => 1.1,    // During fuel shortages
                'default' => 1.0
            ]
        ];

        // Determine season based on month
        $season = 'default';
        if (in_array($month, [10, 11, 12, 1, 2])) {
            $season = 'maha';
        } elseif (in_array($month, [4, 5, 6, 7, 8])) {
            $season = 'yala';
        }

        foreach ($seasonalFactors as $pattern => $factors) {
            if (str_contains(strtolower($categoryName), $pattern)) {
                return $factors[$season] ?? $factors['default'];
            }
        }

        return 1.0;
    }

    private function generateCostDescription(string $categoryName, FarmingOperation $operation): string
    {
        $crop = $this->determineCropCategory($operation->name);
        $year = $operation->season_start->year;

        $descriptions = [
            'Seeds/Seedlings' => [
                'rice' => "Certified paddy seeds (BG varieties) - {$year}",
                'tea' => "Tea bush seedlings and replanting - {$year}",
                'coconut' => "Coconut seedlings (dwarf varieties) - {$year}",
                'vegetables' => "Vegetable seeds and seedlings - {$year}"
            ],
            'Fertilizers' => [
                'rice' => "Urea, TSP, MOP for paddy cultivation - {$year}",
                'tea' => "NPK fertilizer for tea bushes - {$year}",
                'coconut' => "Coconut fertilizer mixture - {$year}",
                'vegetables' => "Organic and chemical fertilizers - {$year}"
            ],
            'Seasonal Labor' => [
                'rice' => "Paddy field preparation, planting, weeding - {$year}",
                'tea' => "Tea plucking and estate maintenance - {$year}",
                'coconut' => "Coconut harvesting and estate work - {$year}",
                'vegetables' => "Land preparation, planting, harvesting - {$year}"
            ],
            'Transportation' => "Transport to {$crop} market/collection center - {$year}",
            'Processing/Storage' => "{$crop} processing and storage costs - {$year}",
            'Land Rent/Lease' => "Annual land rent for {$crop} cultivation - {$year}",
            'Equipment/Machinery' => "Tractor, tiller, and machinery costs - {$year}",
            'Irrigation/Water' => "Irrigation system and water charges - {$year}",
            'Pesticides/Fungicides' => "Plant protection chemicals - {$year}",
            'Fuel/Diesel' => "Diesel for machinery and transportation - {$year}",
            'Insurance' => "Crop and equipment insurance - {$year}",
            'Property Taxes/Rates' => "Government taxes and local rates - {$year}",
            'Permanent Labor' => "Full-time farm worker wages - {$year}"
        ];

        $categoryDescriptions = $descriptions[$categoryName] ?? [];

        if (is_array($categoryDescriptions)) {
            return $categoryDescriptions[$crop] ?? $categoryDescriptions['vegetables'] ?? "{$categoryName} - {$year}";
        }

        return $categoryDescriptions ?? "{$categoryName} - {$year}";
    }

    // private function calculateIncurredDate(FarmingOperation $operation, CostCategory $category): Carbon
    // {
    //     $seasonStart = $operation->season_start;
    //     $seasonLength = $seasonStart->diffInDays($operation->season_end);

    private function calculateIncurredDate(FarmingOperation $operation, CostCategory $category): Carbon
    {
        $seasonStart = $operation->season_start;
        $seasonLength = $seasonStart->diffInDays($operation->season_end);

        // Sri Lankan agricultural timing
        $timingPatterns = [
            'Seeds/Seedlings' => 0.05,        // Very early in season
            'Land Rent/Lease' => 0.02,        // At the beginning
            'Equipment/Machinery' => 0.08,
            'Fertilizers' => 0.25,            // Multiple applications
            'Pesticides/Fungicides' => 0.35,  // Mid-season
            'Irrigation/Water' => 0.40,       // Throughout season
            'Seasonal Labor' => 0.50,         // Peak mid-season
            'Fuel/Diesel' => 0.45,
            'Transportation' => 0.85,         // Near harvest
            'Processing/Storage' => 0.90,     // Post-harvest
            'Insurance' => 0.10,
            'Property Taxes/Rates' => 0.15,
            'Permanent Labor' => 0.30
        ];

        $timing = $timingPatterns[$category->name] ?? 0.5;
        $daysFromStart = (int) ($seasonLength * $timing);

        return $seasonStart->copy()->addDays($daysFromStart);
    }

    private function generateExternalFactors(FarmingOperation $operation, CostCategory $category): array
    {
        $year = $operation->season_start->year;

        // Sri Lankan economic indicators by year
        $economicFactors = [
            2020 => ['fuel_price' => 120, 'usd_rate' => 185, 'inflation' => 4.6],
            2021 => ['fuel_price' => 135, 'usd_rate' => 198, 'inflation' => 6.0],
            2022 => ['fuel_price' => 450, 'usd_rate' => 365, 'inflation' => 25.2], // Crisis year
            2023 => ['fuel_price' => 380, 'usd_rate' => 325, 'inflation' => 15.8],
            2024 => ['fuel_price' => 350, 'usd_rate' => 310, 'inflation' => 8.5],
            2025 => ['fuel_price' => 340, 'usd_rate' => 305, 'inflation' => 6.0]
        ];

        $factors = $economicFactors[$year] ?? $economicFactors[2024];

        return [
            'fuel_price_lkr' => $factors['fuel_price'],
            'usd_exchange_rate' => $factors['usd_rate'],
            'inflation_rate' => $factors['inflation'],
            'labor_rate_daily' => round(1500 * (1 + ($factors['inflation'] / 100)), 2),
            'fertilizer_subsidy' => $year >= 2022 ? false : true, // Subsidy removed in 2022
            'import_restrictions' => $year >= 2021 && $year <= 2023,
            'province' => $operation->location,
            'monsoon_impact' => rand(0, 10) > 7 ? 'high' : (rand(0, 10) > 4 ? 'moderate' : 'low')
        ];
    }

    private function getCropType(string $crop): string
    {
        return match ($crop) {
            'rice', 'vegetables', 'fruits', 'cinnamon' => 'crops',
            default => 'crops'
        };
    }

    private function getYieldUnit(string $crop): string
    {
        return match ($crop) {
            'rice' => 'bushels',
            'tea' => 'kg',
            'coconut' => 'nuts',
            'vegetables' => 'kg',
            'rubber' => 'kg',
            'cinnamon' => 'kg',
            'fruits' => 'kg',
            default => 'kg'
        };
    }

    private function createCurrentYearOperations(int $year, $categories): void
    {
        // Create fewer current year operations (just planned, not completed)
        $provinces = ['Western', 'Central', 'Southern'];
        $crops = ['rice', 'vegetables', 'tea'];

        foreach ($provinces as $province) {
            foreach ($crops as $crop) {
                $operation = $this->createFarmOperation($year, $province, $crop, 1);
                // Add only planned costs (no actual costs yet)
                $this->command->info("Created planned operation: {$operation->name}");
            }
        }
    }
}
