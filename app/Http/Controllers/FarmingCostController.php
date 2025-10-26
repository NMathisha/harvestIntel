<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\FarmingCost;
use App\Models\CostCategory;
use Illuminate\Http\Request;
use App\Models\CostPrediction;
use App\Models\FarmingOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\CostPredictionService;
use App\Services\FarmingCostCalculator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FarmingCostController extends Controller
{
    private FarmingCostCalculator $calculator;
    private CostPredictionService $predictionService;

    public function __construct(FarmingCostCalculator $calculator, CostPredictionService $predictionService)
    {
        $this->calculator = $calculator;
        $this->predictionService = $predictionService;
    }

    /**
     * Dashboard statistics - Overview of entire system
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        try {
            $cacheKey = 'dashboard_stats_' . Carbon::now()->format('Y-m-d-H');

            $stats = Cache::remember($cacheKey, 3600, function () {
                // Basic counts
                $totalOperations = FarmingOperation::count();
                $activeOperations = FarmingOperation::active()->count();
                $completedOperations = FarmingOperation::completed()->count();
                $totalCosts = FarmingCost::count();
                $totalCategories = CostCategory::count();

                // Financial summary
                $totalSpending = FarmingCost::sum('amount');
                $avgCostPerOperation = $totalOperations > 0 ? $totalSpending / $totalOperations : 0;

                // ML Model statistics
                $totalPredictions = CostPrediction::count();
                $accuratePredictions = CostPrediction::where('prediction_error', '<=', 0.2)->count();
                $predictionAccuracy = $totalPredictions > 0 ?
                    round(($accuratePredictions / $totalPredictions) * 100, 1) : 0;

                // Recent activity (last 30 days)
                $recentOperations = FarmingOperation::where('created_at', '>=', now()->subDays(30))->count();
                $recentCosts = FarmingCost::where('created_at', '>=', now()->subDays(30))->count();
                $recentSpending = FarmingCost::where('created_at', '>=', now()->subDays(30))->sum('amount');

                // Cost breakdown by type
                $fixedCosts = FarmingCost::whereHas('category', function ($query) {
                    $query->where('type', 'fixed');
                })->sum('amount');

                $variableCosts = FarmingCost::whereHas('category', function ($query) {
                    $query->where('type', 'variable');
                })->sum('amount');

                // Operations by type
                $operationsByType = FarmingOperation::select('type', DB::raw('COUNT(*) as count'))
                    ->groupBy('type')
                    ->pluck('count', 'type')
                    ->toArray();

                // Top spending categories
                $topCategories = FarmingCost::select(
                    'cost_categories.name',
                    DB::raw('SUM(farming_costs.amount) as total'),
                    DB::raw('COUNT(*) as transaction_count')
                )
                    ->join('cost_categories', 'farming_costs.cost_category_id', '=', 'cost_categories.id')
                    ->groupBy('cost_categories.id', 'cost_categories.name')
                    ->orderBy('total', 'desc')
                    ->limit(5)
                    ->get();

                // Monthly spending trend (last 12 months)
                $monthlyTrend = FarmingCost::select(
                    DB::raw('DATE_FORMAT(incurred_date, "%Y-%m") as month'),
                    DB::raw('SUM(amount) as total'),
                    DB::raw('COUNT(*) as transactions')
                )
                    ->where('incurred_date', '>=', now()->subMonths(12))
                    ->groupBy('month')
                    ->orderBy('month')
                    ->get();

                // Average cost per acre across all operations
                $avgCostPerAcre = DB::table('farming_operations')
                    ->join('farming_costs', 'farming_operations.id', '=', 'farming_costs.farming_operation_id')
                    ->select(DB::raw('SUM(farming_costs.amount) / SUM(farming_operations.total_acres) as avg_per_acre'))
                    ->value('avg_per_acre') ?? 0;

                return [
                    'overview' => [
                        'total_operations' => $totalOperations,
                        'active_operations' => $activeOperations,
                        'completed_operations' => $completedOperations,
                        'total_cost_records' => $totalCosts,
                        'total_categories' => $totalCategories,
                        'data_health' => $this->calculateDataHealth($totalOperations, $totalCosts)
                    ],
                    'financial_summary' => [
                        'total_spending' => round($totalSpending, 2),
                        'avg_cost_per_operation' => round($avgCostPerOperation, 2),
                        'avg_cost_per_acre' => round($avgCostPerAcre, 2),
                        'fixed_costs' => round($fixedCosts, 2),
                        'variable_costs' => round($variableCosts, 2),
                        'fixed_percentage' => $totalSpending > 0 ? round(($fixedCosts / $totalSpending) * 100, 1) : 0,
                        'variable_percentage' => $totalSpending > 0 ? round(($variableCosts / $totalSpending) * 100, 1) : 0
                    ],
                    'ml_performance' => [
                        'total_predictions' => $totalPredictions,
                        'prediction_accuracy' => $predictionAccuracy,
                        'accurate_predictions' => $accuratePredictions,
                        'models_status' => $totalPredictions > 0 ? 'Active' : 'Needs Training'
                    ],
                    'recent_activity' => [
                        'new_operations_30d' => $recentOperations,
                        'new_costs_30d' => $recentCosts,
                        'spending_30d' => round($recentSpending, 2)
                    ],
                    'operations_by_type' => $operationsByType,
                    'top_spending_categories' => $topCategories->map(function ($item) {
                        return [
                            'name' => $item->name,
                            'total' => round($item->total, 2),
                            'transactions' => $item->transaction_count
                        ];
                    }),
                    'monthly_trend' => $monthlyTrend->map(function ($item) {
                        return [
                            'month' => $item->month,
                            'spending' => round($item->total, 2),
                            'transactions' => $item->transactions
                        ];
                    }),
                    'generated_at' => now()
                ];
            });

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error("Dashboard stats error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve dashboard statistics',
                'error_code' => 'STATS_ERROR'
            ], 500);
        }
    }

    /**
     * Calculate data health score
     */
    private function calculateDataHealth(int $operations, int $costs): array
    {
        $avgCostsPerOperation = $operations > 0 ? $costs / $operations : 0;

        // Ideal: 10-15 costs per operation (covers all categories)
        if ($operations === 0) {
            $score = 0;
            $status = 'No Data';
            $recommendation = 'Create your first farming operation to get started';
        } elseif ($avgCostsPerOperation >= 10) {
            $score = 100;
            $status = 'Excellent';
            $recommendation = 'Data quality is excellent. ML models should perform well.';
        } elseif ($avgCostsPerOperation >= 7) {
            $score = 80;
            $status = 'Good';
            $recommendation = 'Good data coverage. Consider adding more cost details.';
        } elseif ($avgCostsPerOperation >= 5) {
            $score = 60;
            $status = 'Fair';
            $recommendation = 'Add more cost categories for better predictions.';
        } else {
            $score = 30;
            $status = 'Poor';
            $recommendation = 'Add more detailed cost information to improve ML accuracy.';
        }

        return [
            'score' => $score,
            'status' => $status,
            'avg_costs_per_operation' => round($avgCostsPerOperation, 1),
            'recommendation' => $recommendation
        ];
    }

    /**
     * Get all operations with filtering and pagination
     */
    public function getOperations(Request $request)
    {
        try {
            $query = FarmingOperation::query();

            // Apply filters
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('status')) {
                switch ($request->status) {
                    case 'active':
                        $query->active();
                        break;
                    case 'completed':
                        $query->completed();
                        break;
                    case 'planned':
                        $query->where('season_start', '>', now());
                        break;
                }
            }

            if ($request->has('location')) {
                $query->where('location', 'LIKE', '%' . $request->location . '%');
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'season_start');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $operations = $query->paginate($perPage);

            // Add computed fields
            $operations->getCollection()->transform(function ($operation) {
                // $operations->transform(function ($operation) {
                return [
                    'id' => $operation->id,
                    'name' => $operation->name,
                    'type' => $operation->type,
                    'total_acres' => $operation->total_acres,
                    'season_start' => $operation->season_start->format('Y-m-d'),
                    'season_end' => $operation->season_end->format('Y-m-d'),
                    'season_length_days' => $operation->season_length,
                    'expected_yield' => $operation->expected_yield,
                    'yield_unit' => $operation->yield_unit,
                    'commodity_price' => $operation->commodity_price,
                    'location' => $operation->location,
                    'status' => $operation->isCompleted() ? 'Completed' : ($operation->isActive() ? 'Active' : 'Planned'),
                    'total_costs' => $operation->total_costs,
                    'cost_per_acre' => $operation->cost_per_acre,
                    'created_at' => $operation->created_at,
                    'updated_at' => $operation->updated_at
                ];
            });

            // If this is an AJAX request (pagination links), return only the table partial
            if ($request->ajax()) {
                return view('components.ope_table')->with([
                    'operations' => $operations
                ]);
            }

            return view('pages.operations')->with([
                'operations' => $operations
            ]);
        } catch (\Exception $e) {
            Log::error("Get operations error", ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve operations'
            ], 500);
        }
    }

    /**
     * Show single operation with detailed information
     */
    public function showOperation(Request $request, int $operationId): JsonResponse
    {
        try {
            $operation = FarmingOperation::with(['costs.category'])->findOrFail($operationId);

            $costSummary = $this->calculator->calculateTotalCosts($operation);

            return response()->json([
                'success' => true,
                'operation' => [
                    'id' => $operation->id,
                    'name' => $operation->name,
                    'type' => $operation->type,
                    'total_acres' => $operation->total_acres,
                    'season_start' => $operation->season_start->format('Y-m-d'),
                    'season_end' => $operation->season_end->format('Y-m-d'),
                    'season_length_days' => $operation->season_length,
                    'expected_yield' => $operation->expected_yield,
                    'yield_unit' => $operation->yield_unit,
                    'commodity_price' => $operation->commodity_price,
                    'location' => $operation->location,
                    'weather_data' => $operation->weather_data,
                    'status' => $operation->isCompleted() ? 'Completed' : ($operation->isActive() ? 'Active' : 'Planned'),
                    'created_at' => $operation->created_at,
                    'updated_at' => $operation->updated_at
                ],
                'cost_summary' => $costSummary,
                'costs' => $operation->costs->map(function ($cost) {
                    return [
                        'id' => $cost->id,
                        'category' => $cost->category->name,
                        'category_type' => $cost->category->type,
                        'description' => $cost->description,
                        'amount' => $cost->amount,
                        'incurred_date' => $cost->incurred_date->format('Y-m-d'),
                        'quantity' => $cost->quantity,
                        'unit' => $cost->unit,
                        'unit_price' => $cost->unit_price,
                        'cost_per_acre' => $cost->cost_per_acre
                    ];
                })
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Operation not found',
                'error_code' => 'OPERATION_NOT_FOUND'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Show operation error", [
                'operation_id' => $operationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve operation details'
            ], 500);
        }
    }

    /**
     * Get all costs for an operation
     */
    public function getCosts(Request $request, int $operationId): JsonResponse
    {
        try {
            $operation = FarmingOperation::findOrFail($operationId);

            $query = $operation->costs()->with('category');

            // Filter by category type
            if ($request->has('type')) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('type', $request->type);
                });
            }

            // Filter by date range
            if ($request->has('from_date')) {
                $query->where('incurred_date', '>=', $request->from_date);
            }

            if ($request->has('to_date')) {
                $query->where('incurred_date', '<=', $request->to_date);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'incurred_date');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $costs = $query->get();

            $total = $costs->sum('amount');

            return response()->json([
                'success' => true,
                'operation' => [
                    'id' => $operation->id,
                    'name' => $operation->name
                ],
                'costs' => $costs->map(function ($cost) {
                    return [
                        'id' => $cost->id,
                        'category_id' => $cost->cost_category_id,
                        'category_name' => $cost->category->name,
                        'category_type' => $cost->category->type,
                        'description' => $cost->description,
                        'amount' => $cost->amount,
                        'incurred_date' => $cost->incurred_date->format('Y-m-d'),
                        'quantity' => $cost->quantity,
                        'unit' => $cost->unit,
                        'unit_price' => $cost->unit_price,
                        'external_factors' => $cost->external_factors,
                        'created_at' => $cost->created_at
                    ];
                }),
                'summary' => [
                    'total_costs' => round($total, 2),
                    'cost_count' => $costs->count(),
                    'avg_cost' => $costs->count() > 0 ? round($total / $costs->count(), 2) : 0
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Operation not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Get costs error", ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve costs'
            ], 500);
        }
    }

    /**
     * Get all cost categories
     */
    public function getCategories(Request $request): JsonResponse
    {
        try {
            $query = CostCategory::query();

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('predictable_only')) {
                $query->where('is_predictable', true);
            }

            $categories = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'categories' => $categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'type' => $category->type,
                        'description' => $category->description,
                        'is_predictable' => $category->is_predictable,
                        'typical_percentage' => $category->typical_percentage,
                        'average_cost' => $category->average_cost,
                        'total_historical_costs' => $category->total_historical_costs
                    ];
                })
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve categories'
            ], 500);
        }
    }

    /**
     * Update existing operation
     */
    public function updateOperation(Request $request, int $operationId): JsonResponse
    {
        try {
            $operation = FarmingOperation::findOrFail($operationId);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'type' => 'sometimes|in:crops,livestock,mixed',
                'total_acres' => 'sometimes|numeric|min:0.01',
                'season_start' => 'sometimes|date',
                'season_end' => 'sometimes|date|after:season_start',
                'expected_yield' => 'nullable|numeric|min:0',
                'yield_unit' => 'nullable|string|max:50',
                'commodity_price' => 'nullable|numeric|min:0',
                'location' => 'nullable|string|max:255',
                'weather_data' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $operation->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Operation updated successfully',
                'operation' => $operation
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Operation not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update operation'
            ], 500);
        }
    }

    /**
     * Update existing cost
     */
    public function updateCost(Request $request, int $costId): JsonResponse
    {
        try {
            $cost = FarmingCost::findOrFail($costId);

            $validator = Validator::make($request->all(), [
                'description' => 'sometimes|string|max:255',
                'amount' => 'sometimes|numeric|min:0.01',
                'incurred_date' => 'sometimes|date',
                'quantity' => 'nullable|numeric|min:0',
                'unit' => 'nullable|string|max:50',
                'external_factors' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $cost->update($validator->validated());

            // Invalidate cache
            $operation = $cost->operation;
            $operation->touch();

            return response()->json([
                'success' => true,
                'message' => 'Cost updated successfully',
                'cost' => $cost
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Cost not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update cost'
            ], 500);
        }
    }

    /**
     * Soft delete operation
     */
    public function deleteOperation(Request $request, int $operationId): JsonResponse
    {
        try {
            $operation = FarmingOperation::findOrFail($operationId);
            $operation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Operation deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Operation not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete operation'
            ], 500);
        }
    }

    /**
     * Soft delete cost
     */
    public function deleteCost(Request $request, int $costId): JsonResponse
    {
        try {
            $cost = FarmingCost::findOrFail($costId);
            $cost->delete();

            // Invalidate cache
            $cost->operation->touch();

            return response()->json([
                'success' => true,
                'message' => 'Cost deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Cost not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete cost'
            ], 500);
        }
    }

    /**
     * Compare multiple operations
     */
    public function compareOperations(Request $request)
    {
        try {
            $operationIds = $request->input('operation_ids', []);

            $validator = Validator::make(['operation_ids' => $operationIds], [
                'operation_ids' => 'required|array|min:2',
                'operation_ids.*' => 'exists:farming_operations,id'
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $operations = FarmingOperation::whereIn('id', $operationIds)->get();

            if (!method_exists($this->calculator, 'compareOperations')) {
                throw new \Exception("compareOperations method not found in calculator");
            }

            $comparison = $this->calculator->compareOperations($operations);

            return view('pages.compare_oper_stats')->with([
                'comparison' => $comparison,
                'operations' => $operations

            ]);
        } catch (\Exception $e) {
            Log::error("Compare operations error", ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to compare operations');
        }
    }

    public function getOperationById(int $id)
    {
        $operation = FarmingOperation::where('deleted_at', null)->where('id', $id)->first();
        return $operation;
    }



    public function predictCosts(int $operationId)
    {
        try {
            $operation = FarmingOperation::findOrFail($operationId);

            if ($operation->total_acres <= 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'Operation must have valid acreage for predictions',
                    'error_code' => 'INVALID_OPERATION'
                ], 400);
            }

            // Generate predictions from service
            $predictions = $this->predictionService->predictAllCostsForOperation($operation);

            // Persist predictions into DB (upsert per category + target_date)
            $items = $predictions['predictions'] ?? [];
            $now = now();
            $savedCount = 0;

            foreach ($items as $item) {
                // Defensive mapping for different service payload keys
                $categoryId = $item['cost_category_id'] ?? $item['category_id'] ?? null;
                $amount = $item['predicted_amount'] ?? $item['amount'] ?? null;
                $confidence = $item['confidence_score'] ?? $item['confidence'] ?? null;
                $factors = $item['prediction_factors'] ?? $item['factors'] ?? null;
                $model = $item['model_used'] ?? $item['model'] ?? (($predictions['data_status']['fallback_used'] ?? false) ? 'fallback' : 'ml');
                $target = $item['target_date'] ?? $item['date'] ?? ($operation->season_end?->toDateString());

                // Skip invalid or non-positive predictions
                if (!$categoryId || $amount === null) {
                    \Log::warning('Skipping malformed prediction item', ['operation_id' => $operation->id, 'item' => $item]);
                    continue;
                }
                if (!is_numeric($amount)) {
                    \Log::warning('Skipping non-numeric prediction amount', ['operation_id' => $operation->id, 'amount' => $amount]);
                    continue;
                }
                if ((float)$amount <= 0) {
                    // If your business rules allow zeros, remove this guard
                    continue;
                }

                CostPrediction::updateOrCreate(
                    [
                        'farming_operation_id' => $operation->id,
                        'cost_category_id' => $categoryId,
                        'target_date' => $target,
                    ],
                    [
                        'predicted_amount' => $amount,
                        'confidence_score' => $confidence,
                        'prediction_factors' => $factors,
                        'model_used' => $model,
                        'prediction_date' => $now,
                    ]
                );
                $savedCount++;
            }

            // Sanitize errors
            $errors = $predictions['errors'] ?? [];
            $safeErrors = [];
            foreach ($errors as $key => $message) {
                $safeErrors[$key] = is_string($message) ? $message : 'Unknown error';
            }

            // Fallback status
            $fallbackUsed = $predictions['data_status']['fallback_used'] ?? false;

            // Reload saved predictions for accurate display
            $saved = CostPrediction::where('farming_operation_id', $operation->id)
                ->with('category')
                ->get();

            return view('components.prediction-summary', [
                'operation' => [
                    'id' => $operation->id,
                    'name' => $operation->name,
                    'type' => $operation->type,
                    'acres' => $operation->total_acres,
                    'season_start' => $operation->season_start->format('Y-m-d'),
                    'season_end' => $operation->season_end->format('Y-m-d')
                ],
                'predictions' => array_merge($predictions, [
                    'errors' => $safeErrors,
                    'saved' => $saved,
                    'saved_count' => $savedCount,
                ]),
                'metadata' => [
                    'generated_at' => $now,
                    'prediction_method' => $fallbackUsed ? 'Industry Standards (Fallback)' : 'Machine Learning Models',
                    'cache_duration' => '1 hour'
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Farming operation not found',
                'error_code' => 'OPERATION_NOT_FOUND'
            ], 404);
        } catch (\Exception $e) {
            return view('components.prediction-summary', [
                'error' => 'Failed to generate predictions: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Comprehensive cost analysis endpoint
     */
    public function costAnalysis(Request $request, int $operationId): JsonResponse
    {
        try {
            // Fetch costs and perform calculations
            $actualCosts = $this->getActualCosts($operationId);
            $predictedCosts = $this->getPredictedCosts($operationId);

            // Ensure these are floats
            $totalActualCosts = (float)$actualCosts['total'] ?? 0;
            $totalPredictedCosts = (float)$predictedCosts['total'] ?? 0;

            // Calculate variance
            $variance = $totalActualCosts - $totalPredictedCosts;
            $variancePercentage = $totalActualCosts > 0 ? ($variance / $totalActualCosts) * 100 : 0;

            return response()->json([
                'success' => true,
                'analysis' => [
                    'total_actual_costs' => $totalActualCosts,
                    'total_predicted_costs' => $totalPredictedCosts,
                    'variance' => $variance,
                    'variance_percentage' => $variancePercentage,
                    'risk_level' => $this->determineRiskLevel($variancePercentage),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Cost analysis failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function determineRiskLevel(float $variancePercentage): string
    {
        if ($variancePercentage < -20) {
            return 'High';
        } elseif ($variancePercentage < 0) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }
    public function getActualCosts(int $operationId): array
    {
        // Fetch actual costs for the given operation
        $costs = FarmingCost::where('farming_operation_id', $operationId)->get();

        // Calculate total costs
        $total = $costs->sum('amount');

        return [
            'total' => $total,
            'costs' => $costs
        ];
    }

    public function getPredictedCosts(int $operationId): array
    {
        // Fetch predicted costs saved in DB for the given operation
        // If multiple target_dates exist, sum the latest per category
        $predictions = CostPrediction::where('farming_operation_id', $operationId)
            ->whereNull('deleted_at')
            ->get()
            ->groupBy('cost_category_id')
            ->map(function ($group) {
                return $group->sortByDesc(function ($p) {
                    return [$p->target_date, $p->prediction_date];
                })->first();
            });

        $list = $predictions->values();
        $total = $list->sum(function ($p) { return (float) $p->predicted_amount; });

        return [
            'total' => (float) $total,
            'predicted_costs' => $list
        ];
    }

    public function trainModel(Request $request, int $categoryId): JsonResponse
    {
        try {
            $category = CostCategory::findOrFail($categoryId);

            if (!$category->is_predictable) {
                return response()->json([
                    'success' => false,
                    'error' => 'Category is marked as non-predictable',
                    'error_code' => 'CATEGORY_NOT_PREDICTABLE'
                ], 400);
            }

            $result = $this->predictionService->trainModelForCategory($category);

            return response()->json([
                'success' => true,
                'message' => "Model trained successfully for category: {$category->name}",
                'training_results' => $result,
                'recommendations' => $this->generateRecommendations($result)
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Cost category not found',
                'error_code' => 'CATEGORY_NOT_FOUND'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Model training API error", [
                'category_id' => $categoryId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Model training failed: ' . $e->getMessage(),
                'error_code' => 'TRAINING_FAILED'
            ], 500);
        }
    }
    private function generateRecommendations(array $trainingResults): array
    {
        $recommendations = [];

        // Example: Use feature importance to suggest actionable insights
        if (!empty($trainingResults['feature_importance'])) {
            foreach ($trainingResults['feature_importance'] as $feature => $importance) {
                if ($importance > 0.5) {
                    $recommendations[] = "Focus on optimizing '{$feature}' as it strongly influences cost.";
                } elseif ($importance < 0.1) {
                    $recommendations[] = "Consider deprioritizing '{$feature}' — it has minimal impact on cost.";
                }
            }
        }

        // Example: Use average predicted cost to guide budgeting
        if (isset($trainingResults['average_predicted_cost'])) {
            $avgCost = number_format($trainingResults['average_predicted_cost'], 2);
            $recommendations[] = "Expected average cost for this category is around {$avgCost}. Plan accordingly.";
        }

        // Example: Use model accuracy to advise confidence
        if (isset($trainingResults['model_accuracy'])) {
            $accuracy = round($trainingResults['model_accuracy'] * 100, 2);
            $recommendations[] = "Model accuracy is {$accuracy}%. Use predictions with appropriate caution.";
        }

        // Fallback if no insights available
        if (empty($recommendations)) {
            $recommendations[] = "Model trained successfully, but no specific recommendations could be derived.";
        }

        return $recommendations;
    }

    /**
     * Train all ML models endpoint
     */
    public function trainAllModels(Request $request): JsonResponse
    {
        try {
            $categories = CostCategory::predictable()->get();
            $results = [];
            $successCount = 0;

            foreach ($categories as $category) {
                try {
                    $result = $this->predictionService->trainModelForCategory($category);
                    $results[$category->name] = [
                        'status' => 'success',
                        'model_type' => $result['model_type'],
                        'accuracy' => $result['mape'],
                        'samples' => $result['sample_count']
                    ];
                    $successCount++;
                } catch (\Exception $e) {
                    $results[$category->name] = [
                        'status' => 'failed',
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Trained {$successCount} out of " . $categories->count() . " models",
                'results' => $results,
                'success_rate' => round(($successCount / $categories->count()) * 100, 1)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to train models: ' . $e->getMessage()
            ], 500);
        }
    }



public function createFarmingOperation(Request $request)
{
    // $validated = $request->validate([
    //     'name' => 'required|string|max:255',
    //     'type' => 'required|string|max:100',
    //     'total_acres' => 'required|numeric|min:0',
    //     'season_start' => 'required|date',
    //     'season_end' => 'required|date|after_or_equal:season_start',
    //     'expected_yield' => 'required|numeric|min:0',
    //     'yield_unit' => 'required|string|max:50',
    //     'weather_data' => 'nullable|json',
    //     'commodity_price' => 'required|numeric|min:0',
    //     'location' => 'required|string|max:255',
    // ]);

    $operation = FarmingOperation::create($request->all());

    return response()->json([
        'message' => 'Farming operation created successfully.',
        'data' => $operation
    ], 201);
}

}
