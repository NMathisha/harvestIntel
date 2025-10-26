<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\View\View;
use App\Models\FarmingCost;
use Illuminate\Http\Request;
use App\Models\FarmingOperation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\Paginator;
use App\Services\CostPredictionService;
use App\Services\FarmingOperationService;
use App\Services\OpenMeteoWeatherService;
use Illuminate\Support\Facades\Validator;

class operationController extends Controller
{
    //
    private CostPredictionService $predictionService;
    private OpenMeteoWeatherService $weatherService;

    public function __construct(CostPredictionService $predictionService, OpenMeteoWeatherService $weatherService)
    {

        $this->predictionService = $predictionService;
        $this->weatherService = $weatherService;
    }

    public function index(Request $req)
    {
        $all_operations = FarmingOperation::where('deleted_at', null)->paginate(10);

        if ($req->ajax()) {
            return view('components.ope_table', compact('all_operations'))->render();
        }

        return view('pages.operations', compact('all_operations'));
    }

    public function deleteFarmingOperation(Request $req)
    {
        $operation = FarmingOperation::find($req->id);
        if ($operation) {

            $operation->delete();
            // return response()->json(['status' => 'success', 'message' => 'Operation deleted successfully.']);
            return response()->json('success');
        } else {
            return response()->json(['status' => 'error', 'message' => 'Operation not found.'], 404);
        }
    }

    public function fetchFarmingOperation(Request $req)
    {
        $operation = FarmingOperation::find($req->id);
        if ($operation) {
            return response()->json(['status' => 'success', 'data' => $operation]);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Operation not found.'], 404);
        }
    }

    public function updateFarmingOperation(Request $request): JsonResponse
    {
        // dd($request->all());
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:farming_operations,name',
                'type' => 'required|in:crops,livestock,mixed',
                't_acres' => 'required|numeric|min:0.01|max:999999.99',
                's_start' => 'required|date|after_or_equal:today',
                's_end' => 'required|date|after:season_start',
                'ex_yield' => 'nullable|numeric',
                'un_yield' => 'nullable|string|max:50',
                'c_price' => 'nullable|numeric|min:0|max:99999.99',
                'loca' => 'nullable|string|max:255',
                'y_unit' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'errors' => $validator->errors(),
                    'error_code' => 'VALIDATION_ERROR'
                ], 422);
            }

            $validatedData = $validator->validated();

            $seasonStart = Carbon::parse($validatedData['s_start']);
            $seasonEnd = Carbon::parse($validatedData['s_end']);
            $seasonLength = $seasonStart->diffInDays($seasonEnd);

            $minSeasonLength = match ($validatedData['type']) {
                'crops' => 30,
                'livestock' => 90,
                'mixed' => 60,
            };

            if ($seasonLength < $minSeasonLength) {
                return response()->json([
                    'success' => false,
                    'error' => "Season length must be at least {$minSeasonLength} days for {$validatedData['type']} operations",
                    'error_code' => 'INVALID_SEASON_LENGTH',
                    'current_length' => $seasonLength,
                    'minimum_required' => $minSeasonLength
                ], 422);
            }

            if (isset($validatedData['ex_yield']) && $validatedData['ex_yield'] > 0) {
                $yieldPerAcre = $validatedData['ex_yield'] / $validatedData['t_acres'];
                $maxYieldPerAcre = match ($validatedData['type']) {
                    'crops' => 300,
                    'livestock' => 50,
                    'mixed' => 200,
                };

                if ($yieldPerAcre > $maxYieldPerAcre) {
                    return response()->json([
                        'success' => false,
                        'error' => "Expected yield per acre seems unrealistic for {$validatedData['type']} operations",
                        'error_code' => 'UNREALISTIC_YIELD',
                        'current_yield_per_acre' => round($yieldPerAcre, 2),
                        'maximum_reasonable' => $maxYieldPerAcre,
                        'suggestion' => 'Please verify your expected yield or total acres'
                    ], 422);
                }
            }

            // 🌦️ Fetch weather data using service method
            if (!empty($validatedData['loca'])) {
                try {
                    $weatherData = $this->getWeather(
                        $validatedData['loca'],
                        $validatedData['s_start'],
                        $validatedData['s_end']
                    );

                    if (is_array($weatherData)) {
                        $validatedData['weather_data'] = $weatherData;

                        // Add derived metrics
                        if (isset($weatherData['avg_temperature'], $weatherData['frost_days'])) {
                            $validatedData['weather_data']['frost_risk_index'] = $this->calculateFrostRisk(
                                $weatherData['avg_temperature'],
                                $weatherData['frost_days'],
                                $seasonLength
                            );
                        }

                        if (isset($weatherData['total_rainfall'], $weatherData['avg_temperature'])) {
                            $validatedData['weather_data']['drought_risk_index'] = $this->calculateDroughtRisk(
                                $weatherData['total_rainfall'],
                                $weatherData['avg_temperature'],
                                $seasonLength
                            );
                        }
                    }
                } catch (\Exception $weatherError) {
                    Log::warning("Weather data fetch failed", [
                        'location' => $validatedData['loca'],
                        'season_start' => $validatedData['s_start'],
                        'season_end' => $validatedData['s_end'],
                        'error' => $weatherError->getMessage()
                    ]);
                }
            }

            DB::beginTransaction();

            try {
                // $operation = FarmingOperation::create($validatedData);

                $operation = FarmingOperation::find($request->id);
                $operation->name = $validatedData['name'] ?? null;;
                $operation->type = $validatedData['type'] ?? null;;
                $operation->total_acres = $validatedData['t_acres'] ?? null;;
                $operation->season_start = $validatedData['s_start'] ?? null;;
                $operation->season_end = $validatedData['s_end'] ?? null;;
                $operation->expected_yield = $validatedData['ex_yield'] ?? null;;
                $operation->yield_unit = $validatedData['y_unit'] ?? null;;
                $operation->weather_data = $weatherData ?? null;
                $operation->commodity_price = $validatedData['c_price'] ?? null;;
                $operation->location = $validatedData['loca'] ?? null;;
                $operation->save();
                try {
                    $initialPredictions = $this->predictionService->predictAllCostsForOperation($operation);
                } catch (\Exception $e) {
                    Log::info("Could not generate initial predictions", [
                        'operation_id' => $operation->id,
                        'reason' => $e->getMessage()
                    ]);
                }

                $budgetRecommendations = $this->generateBudgetRecommendations($operation, $initialPredictions);

                DB::commit();

                Log::info("Farming operation created successfully", [
                    'operation_id' => $operation->id,
                    'name' => $operation->name,
                    'type' => $operation->type,
                    'acres' => $operation->total_acres,
                    'created_by' => auth()->id() ?? 'system'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Farming operation created successfully',
                    'operation' => [
                        'id' => $operation->id,
                        'name' => $operation->name,
                        'type' => $operation->type,
                        'total_acres' => $operation->total_acres,
                        'season_start' => $operation->season_start->format('Y-m-d'),
                        'season_end' => $operation->season_end->format('Y-m-d'),
                        'season_length_days' => $seasonLength,
                        'expected_yield' => $operation->expected_yield,
                        'yield_unit' => $operation->yield_unit,
                        'commodity_price' => $operation->commodity_price,
                        'location' => $operation->location,
                        'weather_data' => $operation->weather_data,
                        'status' => $operation->isActive() ? 'Active' : 'Planned',
                        'created_at' => $operation->created_at
                    ],
                    'initial_predictions' => $initialPredictions,
                    'budget_recommendations' => $budgetRecommendations,
                    'next_steps' => [
                        'add_costs' => "POST /api/v1/farming/operations/{$operation->id}/costs",
                        'view_analysis' => "GET /api/v1/farming/operations/{$operation->id}/analysis",
                        'train_models' => "Train ML models with historical data"
                    ]
                ], 201);
            } catch (\Exception $dbError) {
                DB::rollBack();
                throw $dbError;
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Failed to create farming operation", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create farming operation. Please try again or contact support.',
                'error_code' => 'OPERATION_CREATION_FAILED',
                'debug_info' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function show($id)
    {
        $operation = FarmingOperation::findOrFail($id);
        return response()->json($operation);
    }



    public function getAvailableOperations()
    {
        $availableOpe = FarmingOperation::where('deleted_at', null)->whereDate('season_start', '<', now())->paginate(5);
        // where('season_end', '>=', now())->where('deleted_at', null)->
        // return response()->json($availableOperations);
        return view('pages.available_operations', compact('availableOpe'));
    }

    // use Illuminate\Pagination\Paginator;

    public function getOpeCost(Request $request)
    {
        $operationsQuery = FarmingOperation::whereNull('deleted_at')->orderByDesc('id');
        $costsQuery = FarmingCost::whereNull('deleted_at')->orderByDesc('id');

        if ($request->ajax() && $request->has('operation_id')) {
            $costsQuery->where('farming_operation_id', $request->operation_id);
            $costs = $costsQuery->paginate(5, ['*'], 'costs_page');

            // Return a rendered partial view as HTML
            $html = view('components.costs_table_rows', compact('costs'))->render();
            return response()->json(['html' => $html]);
        }

        $operations = $operationsQuery->paginate(5, ['*'], 'operations_page');
        $costs = $costsQuery->paginate(5, ['*'], 'costs_page');

        return view('pages.Opecost', compact('operations', 'costs'));
    }
}
