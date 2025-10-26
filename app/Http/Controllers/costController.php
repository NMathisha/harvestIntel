<?php

namespace App\Http\Controllers;

use App\Models\FarmingCost;
use App\Models\CostCategory;
use Illuminate\Http\Request;
use App\Models\FarmingOperation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\CostPredictionService;
use App\Services\FarmingCostCalculator;
use App\Services\OpenMeteoWeatherService;
use Illuminate\Support\Facades\Validator;

class costController extends Controller
{

    private FarmingCostCalculator $calculator;
    private CostPredictionService $predictionService;
    private OpenMeteoWeatherService $weatherService;

    public function __construct(FarmingCostCalculator $calculator, CostPredictionService $predictionService, OpenMeteoWeatherService $weatherService)
    {
        $this->calculator = $calculator;
        $this->predictionService = $predictionService;
        $this->weatherService = $weatherService;
    }


    public function index()
    {
        $costCategories = CostCategory::where('deleted_at', null)->get();
        $farmingCost = FarmingCost::where('deleted_at', null)->with('operation','category')->paginate(10);
        // dd($farmingCost->operation);

        return view('pages.cost')->with([
            'costCategories' => $costCategories,
            'farmingCost' => $farmingCost
        ]);
    }

    public function editCost(Request $request, $operationId)
    {

        try {
            // First, check if any farming operations exist at all
            if (FarmingOperation::count() === 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'No farming operations exist. Please create a farming operation first.',
                    'error_code' => 'NO_OPERATIONS_EXIST',
                    'action_required' => 'Create a farming operation before adding costs'
                ], 404);
            }

            // Try to find the specific operation
            $operation = FarmingOperation::find($operationId);
            if (!$operation) {
                // Provide helpful information about available operations
                $availableOperations = FarmingOperation::select('id', 'name', 'type')
                    ->limit(5)
                    ->get();

                return response()->json([
                    'success' => false,
                    'error' => "Farming operation with ID {$operationId} not found",
                    'error_code' => 'OPERATION_NOT_FOUND',
                    'available_operations' => $availableOperations,
                    'suggestion' => 'Use one of the available operation IDs or create a new operation'
                ], 404);
            }

            // Check if any cost categories exist
            if (CostCategory::count() === 0) {
                return response()->json([
                    'success' => false,
                    'error' => 'No cost categories exist. Please create cost categories first.',
                    'error_code' => 'NO_CATEGORIES_EXIST',
                    'action_required' => 'Run database seeders to create default categories'
                ], 404);
            }

            // Validate the request data
            $validator = Validator::make($request->all(), [
                'cost_category_id' => 'required|exists:cost_categories,id',
                'description' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0.01|max:999999.99',
                'incurred_date' => 'required|date|before_or_equal:today|after_or_equal:' . $operation->season_start->format('Y-m-d'),
                'quantity' => 'nullable|numeric|min:0|max:99999',
                'unit' => 'nullable|string|max:50',
                'external_factors' => 'nullable|array',
                'external_factors.*' => 'numeric|min:0|max:9999' // Validate external factor values
            ], [
                'incurred_date.after_or_equal' => 'The incurred date cannot be before the operation season start date.',
                'amount.max' => 'The amount cannot exceed $999,999.99',
                'quantity.max' => 'The quantity cannot exceed 99,999'
            ]);

            if ($validator->fails()) {
                // Get available cost categories for better error context
                $availableCategories = CostCategory::select('id', 'name', 'type')
                    ->limit(10)
                    ->get();

                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors(),
                    'error_code' => 'VALIDATION_ERROR',
                    'available_categories' => $availableCategories,
                    'operation_season' => [
                        'start' => $operation->season_start->format('Y-m-d'),
                        'end' => $operation->season_end->format('Y-m-d')
                    ]
                ], 422);
            }

            // Validate that the cost category exists and is not soft deleted
            $costCategory = CostCategory::find($request->cost_category_id);
            if (!$costCategory) {
                return response()->json([
                    'success' => false,
                    'error' => 'Selected cost category not found',
                    'error_code' => 'CATEGORY_NOT_FOUND'
                ], 404);
            }

            // Check for duplicate costs (optional business rule)
            $duplicateCost = $operation->costs()
                ->where('cost_category_id', $request->cost_category_id)
                ->where('description', $request->description)
                ->where('incurred_date', $request->incurred_date)
                ->where('amount', $request->amount)
                ->first();

            if ($duplicateCost) {
                return response()->json([
                    'success' => false,
                    'error' => 'A similar cost entry already exists for this operation',
                    'error_code' => 'DUPLICATE_COST',
                    'existing_cost' => [
                        'id' => $duplicateCost->id,
                        'description' => $duplicateCost->description,
                        'amount' => $duplicateCost->amount,
                        'date' => $duplicateCost->incurred_date->format('Y-m-d')
                    ],
                    'suggestion' => 'Consider updating the existing cost or modify the description'
                ], 409); // Conflict
            }

            // Calculate unit price if quantity is provided
            $validatedData = $validator->validated();
            if (!empty($validatedData['quantity']) && $validatedData['quantity'] > 0) {
                $validatedData['unit_price'] = $validatedData['amount'] / $validatedData['quantity'];
            }

            // Use database transaction for data integrity
            DB::beginTransaction();

            try {
                // Create the cost record
                $cost = $operation->costs()->create($validatedData);

                // Update operation timestamp to invalidate caches
                $operation->touch();

                // Clear related caches
                Cache::forget("total_costs_{$operation->id}_{$operation->updated_at->timestamp}");
                Cache::forget("historical_avg_{$costCategory->id}_{$operation->type}");

                // Calculate updated totals
                $updatedTotals = $this->calculator->calculateTotalCosts($operation->fresh());

                DB::commit();

                // Log successful cost addition
                Log::info("Cost added successfully", [
                    'operation_id' => $operation->id,
                    'cost_id' => $cost->id,
                    'category' => $costCategory->name,
                    'amount' => $cost->amount,
                    'user_id' => auth()->id() ?? 'system'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Cost added successfully',
                    'cost' => [
                        'id' => $cost->id,
                        'description' => $cost->description,
                        'amount' => $cost->amount,
                        'category' => $costCategory->name,
                        'incurred_date' => $cost->incurred_date->format('Y-m-d'),
                        'quantity' => $cost->quantity,
                        'unit' => $cost->unit,
                        'unit_price' => $cost->unit_price,
                        'created_at' => $cost->created_at
                    ],
                    'operation_summary' => [
                        'id' => $operation->id,
                        'name' => $operation->name,
                        'total_costs_before' => $updatedTotals['total_costs'] - $cost->amount,
                        'cost_added' => $cost->amount,
                        'total_costs_after' => $updatedTotals['total_costs']
                    ],
                    'updated_totals' => $updatedTotals,
                    'recommendations' => $this->generateCostRecommendations($cost, $operation, $updatedTotals)
                ], 201);
            } catch (\Exception $dbError) {
                DB::rollBack();
                throw $dbError;
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Farming operation not found',
                'error_code' => 'OPERATION_NOT_FOUND'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Add cost API error", [
                'operation_id' => $operationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to add cost. Please try again or contact support.',
                'error_code' => 'COST_CREATION_FAILED',
                'debug_info' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

public function getCost($id)
{
    try {
        $cost = FarmingCost::with(['operation', 'category'])
            ->where('deleted_at', null)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $cost->id,
                'description' => $cost->description,
                'amount' => $cost->amount,
                'incurred_date' => $cost->incurred_date,
                'quantity' => $cost->quantity,
                'unit' => $cost->unit,
                'unit_price' => $cost->unit_price,
                'operation' => [
                    'id' => $cost->operation->id,
                    'name' => $cost->operation->name,
                    'type' => $cost->operation->type
                ],
                'category' => [
                    'id' => $cost->category->id,
                    'name' => $cost->category->name,
                    'type' => $cost->category->type
                ]
            ]
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'error' => 'Cost not found',
            'error_code' => 'COST_NOT_FOUND'
        ], 404);

    } catch (\Exception $e) {
        Log::error('Error fetching cost:', [
            'id' => $id,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Failed to retrieve cost information',
            'error_code' => 'INTERNAL_ERROR'
        ], 500);
    }
}
}
