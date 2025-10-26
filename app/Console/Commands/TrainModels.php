<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CostCategory;
use App\Services\CostPredictionService;

class TrainModels extends Command
{
    protected $signature = 'ml:train-models';
    protected $description = 'Train ML models for all predictable cost categories';

    protected CostPredictionService $predictionService;

    public function __construct(CostPredictionService $predictionService)
    {
        parent::__construct();
        $this->predictionService = $predictionService;
    }

    public function handle(): int
    {
        $categories = CostCategory::predictable()->get();
        $this->info("Training models for {$categories->count()} categories...");

        $success = 0;

        foreach ($categories as $category) {
            try {
                $result = $this->predictionService->trainModelForCategory($category);

                $mape = isset($result['mape']) ? $result['mape'] : 'n/a';
                $samples = isset($result['sample_count']) ? $result['sample_count'] : 'n/a';
                $modelType = isset($result['model_type']) ? $result['model_type'] : 'n/a';

                $this->line(" - {$category->name}: OK (model={$modelType}, mape={$mape}, samples={$samples})");
                $success++;
            } catch (\Throwable $e) {
                $this->error(" - {$category->name}: FAILED ({$e->getMessage()})");
            }
        }

        $this->info("Done. Success: {$success}/{$categories->count()}");
        return Command::SUCCESS;
    }
}
