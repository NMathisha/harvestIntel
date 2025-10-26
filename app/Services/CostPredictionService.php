<?php

namespace App\Services;

use App\Models\FarmingOperation;
use App\Models\CostCategory;
use App\Models\FarmingCost;
use App\Models\CostPrediction;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\Regressors\RegressionTree;
use Rubix\ML\Regressors\GradientBoost;
use Rubix\ML\Regressors\Ridge;
use Rubix\ML\Transformers\NumericStringConverter;
use Rubix\ML\Pipeline;
use Rubix\ML\CrossValidation\Metrics\MeanAbsoluteError;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CostPredictionService
{
    private array $models = [];
    private array $trainedCategories = [];

    // Configuration constants
    private const MIN_TRAINING_SAMPLES = 10;
    private const CONFIDENCE_THRESHOLD = 0.1;
    private const CACHE_TTL = 3600; // 1 hour
    private const MAX_PREDICTION_ERROR = 0.3; // 30%

    /**
     * Train ML model for a specific cost category with improved error handling
     */
    public function trainModelForCategory(CostCategory|int $category): array
    {
        try {
            $category = $this->resolveCategoryModel($category);

            Log::info("Starting model training for category: {$category->name}");

            // Check if model already trained recently
            $cacheKey = "ml_model_trained_{$category->id}";
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $historicalData = $this->prepareTrainingData($category);
            // dd($historicalData['sample_count']);
            // Use the explicit sample_count from the array, with fallback
            $sampleCount = isset($historicalData['sample_count']) ?
                $historicalData['sample_count'] :
                count($historicalData['samples']);

            if ($sampleCount < self::MIN_TRAINING_SAMPLES) {
                throw new \Exception(
                    "Insufficient training data for category '{$category->name}'. " .
                        "Need at least " . self::MIN_TRAINING_SAMPLES . " samples, got {$sampleCount}"
                );
            }

            // Select and train model
            $model = $this->selectOptimalModel($sampleCount, $category);
            $model->train($historicalData['dataset']);

            // Validate model performance
            $performance = $this->validateModel($model, $historicalData);

            // Store trained model and performance metrics
            $this->models[$category->id] = $model;
            $this->trainedCategories[$category->id] = $performance;

            // Cache results
            Cache::put($cacheKey, $performance, self::CACHE_TTL);

            Log::info("Model training completed for category: {$category->name}", $performance);

            return $performance;
        } catch (\Exception $e) {
            Log::error("Model training failed for category", [
                'category_id' => $category->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }


    /**
     * Predict cost for a specific operation and category with validation
     */
    public function  predictCostForOperation(FarmingOperation|int $operation, CostCategory|int $category): array
    {
        try {
            $operation = $this->resolveOperationModel($operation);
            $category = $this->resolveCategoryModel($category);

            // build feature vector (example keys — adapt to your actual feature names)
            $features = [
                'total_acres' => $operation->total_acres,
                'commodity_price' => $operation->commodity_price,
                'historical_category_avg' => $operation->costs()->where('cost_category_id', $category->id)->avg('amount') ?? 0
            ];

            Log::info('predictCostForOperation - inputs', [
                'operation_id' => $operation->id,
                'category_id' => $category->id,
                'features' => $features
            ]);

            // normalize before sending to model
            $normalized = $this->normalizeFeatures($features);

            // Ensure model is trained
            if (!isset($this->models[$category->id])) {
                $this->trainModelForCategory($category);
            }

            $model = $this->models[$category->id];

            // Make prediction
            $dataset = new Unlabeled([$normalized]);
            $prediction = $model->predict($dataset)[0];

            // Ensure prediction is positive
            $prediction = max(0, $prediction);

            // Calculate confidence score
            $confidence = $this->calculateConfidence($category, $prediction, $operation);

            // after prediction (assume $prediction float and $confidence available)
            $this->validatePrediction($prediction, $features['historical_category_avg']);
            Log::info('predictCostForOperation - output', [
                'operation_id' => $operation->id,
                'category_id' => $category->id,
                'predicted' => $prediction,
                'confidence' => $confidence ?? null
            ]);

            // Store prediction record
                // Store prediction record
        //$predictionRecord = $this->storePrediction($operation->id, $category, $prediction, $confidence, $features);
        $modelClass = get_class($this->models[$category->id] ?? 'Unknown');

        $predictionRecord = new CostPrediction();
        $predictionRecord->farming_operation_id = $operation->id;
        $predictionRecord->cost_category_id = $category->id;
        $predictionRecord->predicted_amount = $prediction;
        $predictionRecord->confidence_score = $confidence;
        $predictionRecord->prediction_factors = $features;
        $predictionRecord->model_used = $modelClass;
        $predictionRecord->prediction_date = Carbon::now();
        $predictionRecord->target_date = $this->estimateTargetDate($operation, $category);
        $predictionRecord->save();


        Log::info('Prediction saved successfully', [
            'prediction_id' => $predictionRecord->id,
            'table' => 'cost_predictions'
        ]);

            // Log::info("Prediction generated", [
            //     'operation_id' => $operation->id,
            //     'category_id' => $category->id,
            //     'predicted_amount' => $prediction,
            //     'confidence' => $confidence
            // ]);
            Log::info('predictions ' ,$prediction);
            // Get sample count with fallback
            $sampleCount = isset($this->trainedCategories[$category->id]['sample_count']) ?
                $this->trainedCategories[$category->id]['sample_count'] : 0;

            return [
                'predicted_amount' => round($prediction, 2),
                'confidence_score' => round($confidence, 4),
                'prediction_id' => $predictionRecord->id,
                'factors_used' => $features,
                'model_info' => [
                    'model_type' => get_class($model),
                    'training_samples' => $sampleCount
                ]
            ];
        } catch (\Exception $e) {
          Log::error("Prediction failed completely", [
            'operation_id' => $operation->id ?? 'unknown',
            'category_id' => $category->id ?? 'unknown',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
        }
    }

    /**
     * Enhanced prediction method that handles lack of historical data
     */
    public function predictAllCostsForOperation(FarmingOperation|int $operation): array
    {
        $operation = $this->resolveOperationModel($operation);
        $this->validateOperationForPrediction($operation);

        Log::info('predictAllCostsForOperation - start', [
            'operation_id' => $operation->id,
            'total_acres' => $operation->total_acres,
            'commodity_price' => $operation->commodity_price,
            'existing_costs_count' => $operation->costs()->count(),
            'existing_costs_total' => $operation->costs()->sum('amount')
        ]);

        // Get predictable categories with optimized query
        $predictableCategories = CostCategory::predictable()
            ->select('id', 'name', 'type', 'typical_percentage')
            ->get();

        if ($predictableCategories->isEmpty()) {
            throw new \Exception('No predictable cost categories found');
        }

        $predictions = [];
        $totalPredicted = 0;
        $errors = [];
        $fallbackUsed = false;

        // Check if we have any historical data at all
        $totalHistoricalCosts = FarmingCost::count();

        DB::beginTransaction();

        try {
            foreach ($predictableCategories as $category) {
                try {
                    // Try ML prediction first
                    $prediction = $this->predictCostForOperation($operation, $category);
                    $predictions[$category->name] = $prediction;
                    $totalPredicted += $prediction['predicted_amount'];
                } catch (\Exception $e) {
                    // If ML fails, use fallback estimation
                    try {
                        $fallbackPrediction = $this->generateFallbackPrediction($operation, $category);
                        $predictions[$category->name] = $fallbackPrediction;
                        $totalPredicted += $fallbackPrediction['predicted_amount'];
                        $fallbackUsed = true;
                    } catch (\Exception $fallbackError) {
                        $errors[$category->name] = $e->getMessage();
                        Log::warning("Both ML and fallback prediction failed", [
                            'category' => $category->name,
                            'ml_error' => $e->getMessage(),
                            'fallback_error' => $fallbackError->getMessage()
                        ]);
                    }
                }
            }

            DB::commit();
// dd($predictions);
            // Calculate additional metrics
            $result = [
                'predictions' => $predictions,
                'total_predicted_cost' => round($totalPredicted, 2),
                'predicted_cost_per_acre' => $operation->total_acres > 0
                    ? round($totalPredicted / $operation->total_acres, 2)
                    : 0,
                'prediction_date' => now(),
                'categories_processed' => count($predictions),
                'categories_failed' => count($errors),
                'success_rate' => count($predictions) > 0
                    ? round((count($predictions) / (count($predictions) + count($errors))) * 100, 1)
                    : 0,
                'data_status' => [
                    'historical_costs_available' => $totalHistoricalCosts,
                    'fallback_used' => $fallbackUsed,
                    'ml_models_trained' => count($this->models),
                    'recommendation' => $totalHistoricalCosts === 0 ?
                        'Add historical cost data to improve predictions' :
                        'Train ML models with existing data'
                ]
            ];

            if (!empty($errors)) {
                $result['errors'] = $errors;
            }

            // Add improvement suggestions
            if ($totalHistoricalCosts === 0) {
                $result['suggestions'] = $this->generateDataImportSuggestions($operation);
            }

            Log::info('predictAllCostsForOperation - summary', [
                'operation_id' => $operation->id,
                'total_predicted' => $totalPredicted ?? null,
                'predictions_count' => isset($predictions) ? count($predictions) : null,
                'per_acre_predicted' => isset($totalPredicted) && $operation->total_acres ? ($totalPredicted / $operation->total_acres) : null,
                'fallback_used' => $fallbackUsed ?? false,
                'errors' => $errors ?? []
            ]);

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate fallback predictions when no historical data exists
     */
    private function generateFallbackPrediction(FarmingOperation $operation, CostCategory $category): array
    {
        // Industry standard cost estimates per acre by category and operation type
        $industryStandards = [
            'crops' => [
                'Seeds/Seedlings' => 120,
                'Fertilizers' => 180,
                'Pesticides' => 85,
                'Fuel' => 95,
                'Seasonal Labor' => 75,
                'Equipment Repairs' => 65,
                'Transportation' => 35,
                'Water/Irrigation' => 45,
                'Land Rent/Mortgage' => 200,
                'Property Taxes' => 25,
                'Equipment Depreciation' => 150,
                'Insurance' => 40,
                'Permanent Labor' => 90
            ],
            'livestock' => [
                'Seeds/Seedlings' => 0, // Not applicable
                'Fertilizers' => 25,    // For pasture
                'Pesticides' => 15,     // Limited use
                'Fuel' => 85,
                'Seasonal Labor' => 55,
                'Equipment Repairs' => 75,
                'Transportation' => 45,
                'Water/Irrigation' => 65,
                'Land Rent/Mortgage' => 150,
                'Property Taxes' => 20,
                'Equipment Depreciation' => 120,
                'Insurance' => 55,
                'Permanent Labor' => 180
            ],
            'mixed' => [
                'Seeds/Seedlings' => 60,
                'Fertilizers' => 100,
                'Pesticides' => 50,
                'Fuel' => 90,
                'Seasonal Labor' => 65,
                'Equipment Repairs' => 70,
                'Transportation' => 40,
                'Water/Irrigation' => 55,
                'Land Rent/Mortgage' => 175,
                'Property Taxes' => 22,
                'Equipment Depreciation' => 135,
                'Insurance' => 48,
                'Permanent Labor' => 135
            ]
        ];

        $baseAmount = $industryStandards[$operation->type][$category->name] ?? 0;

        if ($baseAmount === 0) {
            throw new \Exception("No fallback estimate available for category: {$category->name}");
        }

        // Apply operation-specific adjustments
        $adjustedAmount = $baseAmount * $operation->total_acres;

        // Adjust based on commodity price if available
        if ($operation->commodity_price) {
            $priceMultiplier = $this->calculatePriceMultiplier($operation->commodity_price, $operation->type);
            $adjustedAmount *= $priceMultiplier;
        }

        // Adjust based on expected yield
        if ($operation->expected_yield && $operation->total_acres > 0) {
            $yieldPerAcre = $operation->expected_yield / $operation->total_acres;
            $yieldMultiplier = $this->calculateYieldMultiplier($yieldPerAcre, $operation->type);
            $adjustedAmount *= $yieldMultiplier;
        }

        // Apply regional adjustments based on location
        if ($operation->location) {
            $regionalMultiplier = $this->calculateRegionalMultiplier($operation->location);
            $adjustedAmount *= $regionalMultiplier;
        }

        return [
            'predicted_amount' => round($adjustedAmount, 2),
            'confidence_score' => 0.3, // Low confidence for fallback estimates
            'prediction_method' => 'industry_standards',
            'base_amount_per_acre' => $baseAmount,
            'factors_used' => [
                'acres' => $operation->total_acres,
                'operation_type' => $operation->type,
                'commodity_price_adjustment' => $operation->commodity_price ? true : false,
                'yield_adjustment' => $operation->expected_yield ? true : false,
                'regional_adjustment' => $operation->location ? true : false
            ],
            'note' => 'Estimate based on industry standards. Add historical data for better accuracy.'
        ];
    }

    /**
     * Calculate price multiplier based on commodity prices
     */
    private function calculatePriceMultiplier(float $commodityPrice, string $operationType): float
    {
        // Industry average commodity prices
        $avgPrices = [
            'crops' => 5.50,    // Average corn/soybean price
            'livestock' => 1400, // Average cattle price per head
            'mixed' => 8.00     // Mixed average
        ];

        $avgPrice = $avgPrices[$operationType];
        return min(1.5, max(0.7, $commodityPrice / $avgPrice));
    }

    /**
     * Calculate yield multiplier based on expected yield
     */
    private function calculateYieldMultiplier(float $yieldPerAcre, string $operationType): float
    {
        // Industry average yields per acre
        $avgYields = [
            'crops' => 175,     // bushels per acre
            'livestock' => 2,   // head per acre
            'mixed' => 100      // mixed units
        ];

        $avgYield = $avgYields[$operationType];
        return min(1.3, max(0.8, $yieldPerAcre / $avgYield));
    }

    /**
     * Calculate regional cost multiplier
     */
    private function calculateRegionalMultiplier(string $location): float
    {
        $location = strtolower($location);

        // Regional cost adjustments (relative to baseline)
        // Regional cost adjustments (relative to baseline)
        $regionalMultipliers = [
            'western province' => 1.20,       // Includes Colombo, Gampaha, Kalutara
            'colombo' => 1.25,
            'gampaha' => 1.15,
            'kandy' => 1.10,
            'central province' => 1.10,
            'southern province' => 1.05,
            'galle' => 1.08,
            'matara' => 1.04,
            'nuwara eliya' => 1.00,
            'northern province' => 0.95,
            'jaffna' => 0.92,
            'eastern province' => 0.94,
            'batticaloa' => 0.93,
            'uva province' => 0.90,
            'monaragala' => 0.88,
            'sabaragamuwa province' => 0.92,
            'ratnapura' => 0.91,
            'north central province' => 0.89,
            'anuradhapura' => 0.90,
            'north western province' => 0.93,
            'kurunegala' => 0.94
        ];

        foreach ($regionalMultipliers as $region => $multiplier) {
            if (str_contains($location, $region)) {
                return $multiplier;
            }
        }

        return 1.0; // Default multiplier
    }

    /**
     * Generate suggestions for improving predictions
     */
    private function generateDataImportSuggestions(FarmingOperation $operation): array
    {
        return [
            'immediate_actions' => [
                'action' => 'Import historical cost data',
                'description' => 'Add past farming operations and their costs to improve predictions',
                'benefit' => 'Significantly improves prediction accuracy',
                'endpoint' => 'POST /api/v1/farming/operations/{id}/costs'
            ],
            'sample_data_option' => [
                'action' => 'Use sample data generator',
                'description' => 'Generate realistic sample data based on your operation type',
                'benefit' => 'Quick start with reasonable estimates',
                'endpoint' => 'POST /api/v1/farming/operations/{id}/generate-sample-data'
            ],
            'industry_data' => [
                'action' => 'Current predictions use industry standards',
                'description' => 'Estimates are based on typical costs for ' . $operation->type . ' operations',
                'accuracy' => 'Moderate accuracy (±30%)',
                'improvement' => 'Add 3-5 historical operations for much better accuracy'
            ],
            'data_sources' => [
                'internal_records' => 'Previous farm accounting records',
                'industry_reports' => 'Agricultural extension service data',
                'cooperative_data' => 'Local farming cooperative records',
                'government_data' => 'USDA cost and return studies'
            ]
        ];
    }

    /**
     * Sample data generator for testing and initial setup
     */
    public function generateSampleData(FarmingOperation $operation, int $yearsBack = 3): array
    {
        $categories = CostCategory::all();
        $sampleCosts = [];

        DB::beginTransaction();

        try {
            for ($year = 1; $year <= $yearsBack; $year++) {
                // Create a historical operation
                $historicalOperation = FarmingOperation::create([
                    'name' => $operation->name . " - Sample " . (date('Y') - $year),
                    'type' => $operation->type,
                    'total_acres' => $operation->total_acres * (0.9 + (rand(0, 20) / 100)), // ±10% variation
                    'season_start' => Carbon::now()->subYears($year)->month(4)->day(1),
                    'season_end' => Carbon::now()->subYears($year)->month(10)->day(30),
                    'expected_yield' => $operation->expected_yield * (0.85 + (rand(0, 30) / 100)), // ±15% variation
                    'yield_unit' => $operation->yield_unit,
                    'commodity_price' => $operation->commodity_price * (0.8 + (rand(0, 40) / 100)), // ±20% variation
                    'location' => $operation->location
                ]);

                // Generate realistic costs for each category
                foreach ($categories as $category) {
                    $fallbackPrediction = $this->generateFallbackPrediction($historicalOperation, $category);

                    // Add some randomness to make it realistic
                    $amount = $fallbackPrediction['predicted_amount'] * (0.8 + (rand(0, 40) / 100));

                    $cost = $historicalOperation->costs()->create([
                        'cost_category_id' => $category->id,
                        'description' => "Sample {$category->name} - Year " . (date('Y') - $year),
                        'amount' => $amount,
                        'incurred_date' => $historicalOperation->season_start->addDays(rand(30, 180)),
                        'quantity' => $category->type === 'variable' ? $historicalOperation->total_acres : null,
                        'unit' => $category->type === 'variable' ? 'acres' : null,
                        'unit_price' => $category->type === 'variable' ? $amount / $historicalOperation->total_acres : null
                    ]);

                    $sampleCosts[] = $cost;
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => "Generated {$yearsBack} years of sample data",
                'historical_operations' => $yearsBack,
                'sample_costs' => count($sampleCosts),
                'next_step' => 'Train ML models with: POST /api/v1/ml/train-all-models'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }





    /**
     * Prepare training data with improved data quality checks
     */
    private function prepareTrainingData(CostCategory $category): array
    {
        // Optimized query with eager loading and constraints
        $historicalCosts = FarmingCost::with(['operation:id,type,total_acres,season_start,season_end,weather_data,commodity_price'])
            ->where('cost_category_id', $category->id)
            ->where('amount', '>', 0) // Exclude zero or negative costs
            ->whereHas('operation', function ($query) {
                $query->where('season_end', '<', now())
                    ->where('total_acres', '>', 0); // Valid operations only
            })
            ->orderBy('incurred_date')
            ->get();

        if ($historicalCosts->isEmpty()) {
            throw new \Exception("No historical data found for category: {$category->name}");
        }

        $samples = [];
        $labels = [];
        $skippedCount = 0;

        foreach ($historicalCosts as $cost) {
            try {
                $features = $this->extractFeaturesForOperation($cost->operation, $category, $cost);

                // Validate features quality
                if ($this->areValidFeatures($features) && $cost->amount > 0) {
                    $samples[] = array_values($features); // Ensure numeric array
                    $labels[] = (float) $cost->amount;
                } else {
                    $skippedCount++;
                }
            } catch (\Exception $e) {
                $skippedCount++;
                Log::warning("Skipped training sample", [
                    'cost_id' => $cost->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (empty($samples)) {
            throw new \Exception("No valid training samples for category: {$category->name}");
        }

        Log::info("Training data prepared", [
            'category' => $category->name,
            'valid_samples' => count($samples),
            'skipped_samples' => $skippedCount
        ]);

        $dataset = new Labeled($samples, $labels);

        return [
            'dataset' => $dataset,
            'samples' => $samples,
            'labels' => $labels,
            'skipped_count' => $skippedCount,
            'sample_count' => count($samples)
        ];
    }

    /**
     * Extract features with improved validation and error handling
     */
    private function extractFeaturesForOperation(FarmingOperation $operation, CostCategory $category, ?FarmingCost $cost = null): array
    {
        $features = [
            'acres' => (float) $operation->total_acres,
            'season_length' => (float) $operation->season_length,
            'season_start_month' => (float) $operation->season_start->month,
            'expected_yield' => (float) ($operation->expected_yield ?? 0),
            'commodity_price' => (float) ($operation->commodity_price ?? 0),
        ];

        // Operation type encoding (one-hot)
        $features['type_crops'] = $operation->type === 'crops' ? 1.0 : 0.0;
        $features['type_livestock'] = $operation->type === 'livestock' ? 1.0 : 0.0;
        $features['type_mixed'] = $operation->type === 'mixed' ? 1.0 : 0.0;

        // Weather data with defaults
        if ($operation->weather_data && is_array($operation->weather_data)) {
            $weather = $operation->weather_data;
            $features['avg_temperature'] = (float) ($weather['avg_temperature'] ?? 20);
            $features['total_rainfall'] = (float) ($weather['total_rainfall'] ?? 500);
            $features['frost_days'] = (float) ($weather['frost_days'] ?? 0);
        } else {
            $features['avg_temperature'] = 20.0;
            $features['total_rainfall'] = 500.0;
            $features['frost_days'] = 0.0;
        }

        // Historical context
        $historicalAvg = Cache::remember(
            "historical_avg_{$category->id}_{$operation->type}",
            self::CACHE_TTL,
            function () use ($category, $operation) {
                return FarmingCost::where('cost_category_id', $category->id)
                    ->whereHas('operation', function ($query) use ($operation) {
                        $query->where('type', $operation->type);
                    })
                    ->avg('amount') ?? 0;
            }
        );

        $features['historical_category_avg'] = (float) $historicalAvg;

        // Market conditions from external factors
        if ($cost && $cost->external_factors && is_array($cost->external_factors)) {
            $external = $cost->external_factors;
            $features['fuel_price'] = (float) ($external['fuel_price'] ?? 3.0);
            $features['labor_rate'] = (float) ($external['labor_rate'] ?? 15.0);
            $features['input_price_index'] = (float) ($external['input_price_index'] ?? 100);
        } else {
            // Use current market defaults
            $features['fuel_price'] = 3.0;
            $features['labor_rate'] = 15.0;
            $features['input_price_index'] = 100.0;
        }

        // Validate all features are numeric
        foreach ($features as $key => $value) {
            if (!is_numeric($value) || !is_finite($value)) {
                $features[$key] = 0.0;
            }
        }

        return $features;
    }

    /**
     * Select optimal ML model based on data characteristics
     */
    private function selectOptimalModel(int $sampleCount, CostCategory $category): Pipeline
    {
        $transformer = new NumericStringConverter();

        if ($sampleCount < 50) {
            // Linear model for small datasets - more stable
            // Ridge(alpha, solver)
            return new Pipeline([$transformer], new Ridge(0.1));
        } elseif ($sampleCount < 200) {
            // Decision tree for medium datasets
            // RegressionTree(maxDepth, maxLeafSize, minPurityIncrease, maxFeatures)
            return new Pipeline([$transformer], new RegressionTree(
                8,      // maxDepth
                5,      // maxLeafSize
                1e-7,   // minPurityIncrease
                null    // maxFeatures (null = use all features)
            ));
        } else {
            // Gradient boosting for large datasets
            // GradientBoost(booster, rate, ratio, epochs, minChange, window, holdOut, metric)
            $booster = new RegressionTree(5, 3, 1e-7); // Simple tree as booster

            return new Pipeline([$transformer], new GradientBoost(
                $booster,  // booster
                0.1,       // learning rate
                0.8,       // ratio (subsample ratio)
                100        // epochs
            ));
        }
    }

    /**
     * FIXED: Validate model performance with cross-validation
     * This version correctly handles the data structure returned by prepareTrainingData()
     */
    private function validateModel($model, array $historicalData): array
    {
        // Extract the raw arrays - these are already plain PHP arrays, not Rubix objects
        $samples = $historicalData['samples'];  // Raw 2D array
        $labels = $historicalData['labels'];    // Raw 1D array
        $sampleCount = $historicalData['sample_count']; // Explicit count

        // Create Unlabeled dataset for predictions
        $unlabeledDataset = new Unlabeled($samples);

        // Make predictions on training data for validation
        $predictions = $model->predict($unlabeledDataset);

        // Calculate Mean Absolute Error
        $mae = new MeanAbsoluteError();
        $maeScore = $mae->score($predictions, $labels);

        // Calculate additional metrics manually
        $mse = 0;
        $totalAbsoluteError = 0;
        $totalPercentageError = 0;
        $validPredictions = 0;

        for ($i = 0; $i < count($predictions); $i++) {
            if (isset($labels[$i]) && $labels[$i] > 0) {
                $error = $predictions[$i] - $labels[$i];
                $absError = abs($error);

                // Mean Squared Error component
                $mse += pow($error, 2);

                // Total absolute error
                $totalAbsoluteError += $absError;

                // Percentage error (avoid division by zero)
                $percentageError = ($absError / $labels[$i]) * 100;
                $totalPercentageError += $percentageError;

                $validPredictions++;
            }
        }

        // Calculate final metrics
        $rmse = $validPredictions > 0 ? sqrt($mse / $validPredictions) : 0;
        $mape = $validPredictions > 0 ? ($totalPercentageError / $validPredictions) : 100;
        $avgAbsoluteError = $validPredictions > 0 ? ($totalAbsoluteError / $validPredictions) : 0;

        // Determine reliability thresholds
        $isHighlyReliable = $mape < 15;  // Excellent
        $isReliable = $mape < 30;        // Good
        $isAcceptable = $mape < 50;      // Acceptable

        return [
            'model_type' => get_class($model),
            'mae' => round($maeScore, 2),
            'rmse' => round($rmse, 2),
            'mape' => round($mape, 2),
            'avg_absolute_error' => round($avgAbsoluteError, 2),
            'sample_count' => $sampleCount,
            'valid_predictions' => $validPredictions,
            'trained_at' => now(),
            'reliability_level' => $isHighlyReliable ? 'Excellent' : ($isReliable ? 'Good' : ($isAcceptable ? 'Acceptable' : 'Poor')),
            'is_reliable' => $isReliable,
            'confidence_baseline' => $isHighlyReliable ? 0.9 : ($isReliable ? 0.7 : 0.5)
        ];
    }
    /**
     * Calculate prediction confidence with improved algorithm
     */
    private function calculateConfidence(CostCategory $category, float $prediction, FarmingOperation $operation): float
    {
        try {
            // Get historical costs for similar operations
            $similarOperations = FarmingCost::where('cost_category_id', $category->id)
                ->whereHas('operation', function ($query) use ($operation) {
                    $query->where('type', $operation->type)
                        ->whereBetween('total_acres', [
                            max(1, $operation->total_acres * 0.7),
                            $operation->total_acres * 1.3
                        ]);
                })
                ->whereDate('incurred_date', '>=', now()->subYears(3)) // Recent data only
                ->pluck('amount');

            if ($similarOperations->count() < 3) {
                return 0.3; // Low confidence due to limited data
            }

            $mean = $similarOperations->avg();
            $std = $this->calculateStandardDeviation($similarOperations->toArray());

            if ($std == 0) {
                return $prediction == $mean ? 0.95 : 0.5;
            }

            // Calculate z-score
            $zScore = abs(($prediction - $mean) / $std);

            // Convert z-score to confidence (higher z-score = lower confidence)
            // Using inverse normal distribution approximation
            $confidence = max(0.1, min(0.95, 1 - ($zScore / 4)));

            // Adjust confidence based on historical model performance
            $modelPerformance = $this->trainedCategories[$category->id]['is_reliable'] ?? false;
            if (!$modelPerformance) {
                $confidence *= 0.7; // Reduce confidence for unreliable models
            }

            return round($confidence, 4);
        } catch (\Exception $e) {
            Log::warning("Confidence calculation failed", ['error' => $e->getMessage()]);
            return 0.5; // Default moderate confidence
        }
    }

    /**
     * Store prediction with proper error handling
     */
    private function storePrediction(FarmingOperation $operation, CostCategory $category, float $prediction, float $confidence, array $features): CostPrediction
{
    try {
        $modelClass = get_class($this->models[$category->id] ?? 'Unknown');

        $predictionRecord = CostPrediction::create([
            'farming_operation_id' => $operation->id,
            'cost_category_id' => $category->id,
            'predicted_amount' => $prediction,
            'confidence_score' => $confidence,
            'prediction_factors' => $features,
            'model_used' => $modelClass,
            'prediction_date' => now(),
            'target_date' => $this->estimateTargetDate($operation, $category),
        ]);

        Log::info('Prediction saved successfully', [
            'prediction_id' => $predictionRecord->id,
            'operation_id' => $operation->id,
            'category_id' => $category->id,
            'amount' => $prediction
        ]);

        return $predictionRecord;

    } catch (\Exception $e) {
        Log::error('Failed to save prediction', [
            'error' => $e->getMessage(),
            'operation_id' => $operation->id,
            'category_id' => $category->id,
            'features' => $features
        ]);
        throw $e;
    }
}

    /**
     * Estimate when cost will occur based on category and season
     */
    private function estimateTargetDate(FarmingOperation $operation, CostCategory $category): Carbon
    {
        $seasonLength = $operation->season_length;

        // Smart timing based on category keywords
        $categoryName = strtolower($category->name);
        $timing = match (true) {
            str_contains($categoryName, 'seed') || str_contains($categoryName, 'plant') => 0.05,
            str_contains($categoryName, 'fertilizer') || str_contains($categoryName, 'soil') => 0.15,
            str_contains($categoryName, 'pest') || str_contains($categoryName, 'spray') => 0.4,
            str_contains($categoryName, 'fuel') || str_contains($categoryName, 'maintenance') => 0.5,
            str_contains($categoryName, 'labor') => 0.6,
            str_contains($categoryName, 'harvest') => 0.85,
            str_contains($categoryName, 'storage') || str_contains($categoryName, 'transport') => 0.9,
            $category->type === 'fixed' => 0.1, // Fixed costs usually early
            default => 0.5 // Default to mid-season
        };

        $daysFromStart = (int) ($seasonLength * $timing);
        return $operation->season_start->addDays($daysFromStart);
    }

    /**
     * Update prediction accuracy with model performance tracking
     */
    public function updatePredictionAccuracy(CostPrediction $prediction, float $actualAmount): void
    {
        try {
            DB::transaction(function () use ($prediction, $actualAmount) {
                $error = $prediction->calculateError();

                $prediction->update([
                    'actual_amount' => $actualAmount,
                    'prediction_error' => $error
                ]);

                // Check if model needs retraining
                if ($error && $error > self::MAX_PREDICTION_ERROR) {
                    Log::warning("High prediction error detected", [
                        'prediction_id' => $prediction->id,
                        'category_id' => $prediction->cost_category_id,
                        'error_rate' => $error,
                        'predicted' => $prediction->predicted_amount,
                        'actual' => $actualAmount
                    ]);

                    // Queue model retraining
                    //  dispatch(new \App\Jobs\RetrainModelJob($prediction->cost_category_id));
                }
            });
        } catch (\Exception $e) {
            Log::error("Failed to update prediction accuracy", [
                'prediction_id' => $prediction->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Helper methods for validation and utilities
     */
    private function resolveCategoryModel($category): CostCategory
    {
        if (is_int($category) || is_string($category)) {
            $resolved = CostCategory::find($category);
            if (!$resolved) {
                throw new \InvalidArgumentException("Cost category with ID {$category} not found");
            }
            return $resolved;
        }

        if (!$category instanceof CostCategory) {
            throw new \InvalidArgumentException('Category must be a CostCategory model or valid ID');
        }

        return $category;
    }

    private function resolveOperationModel($operation): FarmingOperation
    {
        if (is_int($operation) || is_string($operation)) {
            $resolved = FarmingOperation::find($operation);
            if (!$resolved) {
                throw new \InvalidArgumentException("Farming operation with ID {$operation} not found");
            }
            return $resolved;
        }

        if (!$operation instanceof FarmingOperation) {
            throw new \InvalidArgumentException('Operation must be a FarmingOperation model or valid ID');
        }

        return $operation;
    }

    private function validateOperationForPrediction(FarmingOperation $operation): void
    {
        if ($operation->total_acres <= 0) {
            throw new \InvalidArgumentException('Operation must have positive acreage');
        }

        if ($operation->season_start->greaterThan($operation->season_end)) {
            throw new \InvalidArgumentException('Invalid season dates');
        }

        if ($operation->isCompleted()) {
            Log::warning("Predicting costs for completed operation", [
                'operation_id' => $operation->id,
                'end_date' => $operation->season_end
            ]);
        }
    }

    private function validateFeatures(array $features): void
    {
        foreach ($features as $key => $value) {
            if (!is_numeric($value) || !is_finite($value)) {
                throw new \InvalidArgumentException("Invalid feature value for {$key}: {$value}");
            }
        }
    }

    private function areValidFeatures(array $features): bool
    {
        foreach ($features as $value) {
            if (!is_numeric($value) || !is_finite($value)) {
                return false;
            }
        }
        return count($features) > 5; // Minimum feature count
    }

    private function calculateStandardDeviation(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / count($values);

        return sqrt($variance);
    }

    /**
     * Get model performance statistics
     */
    public function getModelPerformanceStats(): array
    {
        return Cache::remember('ml_performance_stats', self::CACHE_TTL, function () {
            return CostPrediction::withActuals()
                ->select([
                    'cost_category_id',
                    DB::raw('COUNT(*) as prediction_count'),
                    DB::raw('AVG(prediction_error) as avg_error'),
                    DB::raw('MIN(prediction_error) as min_error'),
                    DB::raw('MAX(prediction_error) as max_error'),
                    DB::raw('AVG(confidence_score) as avg_confidence')
                ])
                ->with('category:id,name')
                ->groupBy('cost_category_id')
                ->get()
                ->keyBy('cost_category_id')
                ->toArray();
        });
    }

    /**
     * Normalize numeric features to stable ranges for model training / prediction
     */
    private function normalizeFeatures(array $features): array
    {
        $scaling = [
            'total_acres' => 10.0,
            'commodity_price' => 100.0,
            'historical_category_avg' => 1000.0,
        ];

        foreach ($features as $k => $v) {
            if (isset($scaling[$k]) && is_numeric($v)) {
                $features[$k] = $v / $scaling[$k];
            }
        }

        return $features;
    }

    /**
     * Validate prediction against historical average to catch gross mismatches
     */
    private function validatePrediction(float $predicted, ?float $historicalAvg): void
    {
        if (empty($historicalAvg) || $historicalAvg == 0.0) {
            return; // nothing to compare against
        }

        $variance = abs(($predicted - $historicalAvg) / $historicalAvg);
        if ($variance > self::MAX_PREDICTION_ERROR) {
            Log::warning('High prediction variance detected', [
                'predicted' => $predicted,
                'historical_avg' => $historicalAvg,
                'variance' => $variance
            ]);
        }
    }
}
