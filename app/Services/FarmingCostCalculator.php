<?php

namespace App\Services;

use App\Models\FarmingOperation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\CostPredictionService;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FarmingCostCalculator
{
    private CostPredictionService $predictionService;
    private const CACHE_TTL = 1800; // 30 minutes


    public function __construct(CostPredictionService $predictionService)
    {
        $this->predictionService = $predictionService;
    }

    /**
     * Calculate total costs with caching and optimization
     */
    public function calculateTotalCosts(FarmingOperation $operation): array
    {
        \Log::info('calculateTotalCosts called', [
            'operation_id' => $operation->id,
            'costs_count' => $operation->costs()->count()
        ]);

        // Ensure categories are loaded for type-based sums
        $costs = $operation->costs()->with('category')->get();

        $fixed = $costs->filter(fn($c) => optional($c->category)->type === 'fixed')->sum('amount');
        $variable = $costs->filter(fn($c) => optional($c->category)->type === 'variable')->sum('amount');
        $total = (float) ($fixed + $variable);
        $costPerAcre = $operation->total_acres > 0 ? (float) ($total / $operation->total_acres) : 0.0;

        $summary = [
            'total_costs' => round($total, 2),
            'fixed_costs' => round((float) $fixed, 2),
            'variable_costs' => round((float) $variable, 2),
            'cost_per_acre' => round($costPerAcre, 2),
        ];

        \Log::info('calculateTotalCosts result', [
            'operation_id' => $operation->id,
            'summary' => $summary
        ]);

        return $summary;
    }

    /**
     * Enhanced analysis with predictions and variance
     */
    public function calculateWithPredictions(FarmingOperation $operation): array
    {
        try {
            $actualSummary = $this->calculateTotalCosts($operation);
            $predictions = $this->predictionService->predictAllCostsForOperation($operation);

            $totalPredicted = (float) ($predictions['total_predicted_cost'] ?? 0);
            $totalActual = (float) ($actualSummary['total_costs'] ?? 0);

            // Calculate variance analysis
            $variance = $totalPredicted - $totalActual;
            $variancePercent = $totalActual > 0 ? round(($variance / $totalActual) * 100, 1) : 0.0;

            // Risk assessment
            $riskLevel = match (true) {
                abs($variancePercent) <= 10 => 'Low',
                abs($variancePercent) <= 25 => 'Medium',
                default => 'High'
            };

            return [
                'operation_info' => [
                    'id' => $operation->id,
                    'name' => $operation->name,
                    'type' => $operation->type,
                    'acres' => $operation->total_acres,
                    'status' => $operation->isCompleted() ? 'Completed' : ($operation->isActive() ? 'Active' : 'Planned')
                ],
                'actual_costs' => $actualSummary,
                'predicted_costs' => $predictions,
                'variance_analysis' => [
                    'absolute_variance' => round($variance, 2),
                    'percentage_variance' => $variancePercent,
                    'risk_level' => $riskLevel,
                    'over_under_budget' => $variance > 0 ? 'Over Budget' : 'Under Budget'
                ],
                'recommendations' => $this->generateRecommendations($actualSummary, $predictions, $operation),
                'analysis_date' => now()
            ];
        } catch (\Exception $e) {
            Log::error('Cost analysis failed', [
                'operation_id' => $operation->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate actionable recommendations based on analysis
     */
    private function generateRecommendations(array $actualCosts, array $predictions, FarmingOperation $operation): array
    {
        $recommendations = [];
        $variance = $predictions['total_predicted_cost'] - $actualCosts['total_costs'];
        $variancePercent = abs($variance / max($actualCosts['total_costs'], 1) * 100);

        // Budget variance recommendations
        if ($variancePercent > 20) {
            $recommendations[] = [
                'type' => 'budget',
                'priority' => 'high',
                'message' => $variance > 0 ?
                    'Costs significantly exceed predictions. Review budget allocation and cost control measures.' :
                    'Costs are well under predictions. Consider if quality/inputs are adequate.',
                'action' => 'Review detailed cost breakdown and adjust future budgets accordingly'
            ];
        }

        // Fixed vs Variable cost analysis
        $fixedRatio = ($actualCosts['total_costs'] ?? 0) > 0 ?
            (($actualCosts['fixed_costs'] ?? 0) / $actualCosts['total_costs']) * 100 : 0;

        if ($fixedRatio > 60) {
            $recommendations[] = [
                'type' => 'cost_structure',
                'priority' => 'medium',
                'message' => 'High fixed cost ratio (' . round($fixedRatio, 1) . '%). Consider variable cost alternatives.',
                'action' => 'Evaluate lease vs buy decisions, explore flexible labor arrangements'
            ];
        }

        // Cost per acre analysis
        $costPerAcre = $actualCosts['cost_per_acre'] ?? 0;
        if ($operation->type === 'crops' && $costPerAcre > 800) {
            $recommendations[] = [
                'type' => 'efficiency',
                'priority' => 'medium',
                'message' => "Cost per acre ($costPerAcre) is above industry average for crop operations.",
                'action' => 'Analyze input costs, labor efficiency, and equipment utilization'
            ];
        }

        // Prediction accuracy recommendations
        if (($predictions['success_rate'] ?? 100) < 80) {
            $recommendations[] = [
                'type' => 'data_quality',
                'priority' => 'low',
                'message' => 'ML prediction accuracy is below optimal. More historical data may improve predictions.',
                'action' => 'Continue recording detailed cost data to improve future predictions'
            ];
        }

        return $recommendations;
    }

    /**
     * Format cost breakdown for better presentation
     */
    private function formatCostBreakdown(Collection $costSummary): array
    {
        return $costSummary->mapWithKeys(function ($item) {
            return [$item->category_name => [
                'total' => round($item->total, 2),
                'type' => $item->type,
                'transaction_count' => $item->item_count,
                'average_per_transaction' => round($item->average, 2),
                'date_range' => [
                    'first' => $item->first_date,
                    'last' => $item->last_date
                ]
            ]];
        })->toArray();
    }

    /**
     * Compare operations for benchmarking
     */
    public function compareOperations(Collection $operations): array
    {
        $comparisons = $operations->map(function ($operation) {
            $summary = $this->calculateTotalCosts($operation);

            return [
                'operation_id' => $operation->id,
                'name' => $operation->name,
                'type' => $operation->type,
                'acres' => $operation->total_acres,
                'season' => $operation->season_start->format('Y'),
                'total_costs' => $summary['total_costs'] ?? 0,
                'cost_per_acre' => $summary['cost_per_acre'] ?? 0,
                'fixed_costs' => $summary['fixed_costs'] ?? 0,
                'variable_costs' => $summary['variable_costs'] ?? 0,
                'profit_margin' => $operation->expected_yield && $operation->commodity_price ?
                    round((($operation->expected_yield * $operation->commodity_price) - ($summary['total_costs'] ?? 0)) /
                        max(($summary['total_costs'] ?? 1), 1) * 100, 2) : null
            ];
        });

        // Calculate benchmarks
        $avgCostPerAcre = $comparisons->avg('cost_per_acre') ?? 0;
        $avgTotalCosts = $comparisons->avg('total_costs') ?? 0;

        return [
            'operations' => $comparisons->toArray(),
            'benchmarks' => [
                'average_cost_per_acre' => round($avgCostPerAcre, 2),
                'average_total_costs' => round($avgTotalCosts, 2),
                'operation_count' => $comparisons->count()
            ],
            'insights' => [
                'most_efficient' => $comparisons->sortBy('cost_per_acre')->first(),
                'highest_cost' => $comparisons->sortByDesc('cost_per_acre')->first(),
                'cost_range' => [
                    'min_per_acre' => round($comparisons->min('cost_per_acre'), 2),
                    'max_per_acre' => round($comparisons->max('cost_per_acre'), 2)
                ]
            ]
        ];
    }

    /**
     * Get weather data
     *
     * POST /api/weather
     * Body: {
     *   "location": "Western Province",
     *   "start_date": "2024-01-01",
     *   "end_date": "2024-12-31"
     * }
     */
}
