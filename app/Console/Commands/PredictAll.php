<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FarmingOperation;
use App\Models\CostPrediction;
use App\Services\CostPredictionService;

class PredictAll extends Command
{
    protected $signature = 'ml:predict-all {--operation_id=}';
    protected $description = 'Generate and persist predictions for all (or a specific) operations';

    public function __construct(private CostPredictionService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $operationId = $this->option('operation_id');

        $query = FarmingOperation::query();
        if ($operationId) {
            $query->where('id', $operationId);
        }

        $ops = $query->get();
        $this->info("Generating predictions for {$ops->count()} operation(s)...");

        $countSaved = 0;
        foreach ($ops as $op) {
            try {
                $pred = $this->service->predictAllCostsForOperation($op);
                $items = $pred['predictions'] ?? [];

                $now = now();

                foreach ($items as $item) {
                    $categoryId = $item['cost_category_id'] ?? $item['category_id'] ?? null;
                    $amount = $item['predicted_amount'] ?? $item['amount'] ?? null;
                    if (!$categoryId || $amount === null) {
                        continue;
                    }

                    $target = $item['target_date'] ?? $item['date'] ?? $op->season_end?->toDateString();

                    CostPrediction::updateOrCreate(
                        [
                            'farming_operation_id' => $op->id,
                            'cost_category_id' => $categoryId,
                            'target_date' => $target,
                        ],
                        [
                            'predicted_amount' => $amount,
                            'confidence_score' => $item['confidence_score'] ?? $item['confidence'] ?? null,
                            'prediction_factors' => $item['prediction_factors'] ?? $item['factors'] ?? null,
                            'model_used' => $item['model_used'] ?? $item['model'] ?? (($pred['data_status']['fallback_used'] ?? false) ? 'fallback' : 'ml'),
                            'prediction_date' => $now,
                        ]
                    );

                    $countSaved++;
                }

                $this->line(" - Operation {$op->id}: saved " . count($items) . " predictions");
            } catch (\Throwable $e) {
                $this->error(" - Operation {$op->id}: FAILED ({$e->getMessage()})");
            }
        }

        $this->info("Completed. Total predictions saved/updated: {$countSaved}");
        return Command::SUCCESS;
    }
}
