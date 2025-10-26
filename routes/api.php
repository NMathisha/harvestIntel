<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FarmingCostController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

Route::prefix('v1/farming')->name('farming.')->group(function () {
    
    // ============================================
    // DASHBOARD & OVERVIEW
    // ============================================
    Route::get('/dashboard/stats', [FarmingCostController::class, 'dashboardStats'])
        ->name('dashboard.stats');
    
    // ============================================
    // FARMING OPERATIONS MANAGEMENT
    // ============================================
    
    // List all operations (with filters)
    Route::get('/operations', [FarmingCostController::class, 'getOperations'])
        ->name('operations.index');
    
    // Get available operations for selection
    Route::get('/operations/available', [FarmingCostController::class, 'getAvailableOperations'])
        ->name('operations.available');
    
    // Compare multiple operations
    Route::post('/operations/compare', [FarmingCostController::class, 'compareOperations'])
        ->name('operations.compare');
    
    // Create new operation
    Route::post('/operations', [FarmingCostController::class, 'createFarmingOperation'])
        ->name('operations.create');
    
    // Get single operation details
    Route::get('/operations/{operation}', [FarmingCostController::class, 'showOperation'])
        ->name('operations.show');
    
    // Update operation
    Route::put('/operations/{operation}', [FarmingCostController::class, 'updateOperation'])
        ->name('operations.update');
    
    // Soft delete operation
    Route::delete('/operations/{operation}', [FarmingCostController::class, 'deleteOperation'])
        ->name('operations.delete');
    
    // ============================================
    // COST MANAGEMENT
    // ============================================
    
    // Get all costs for an operation
    Route::get('/operations/{operation}/costs', [FarmingCostController::class, 'getCosts'])
        ->name('operations.costs.index');
    
    // Add new cost to operation
    Route::post('/operations/{operation}/costs', [FarmingCostController::class, 'addCost'])
        ->name('operations.costs.create');
    
    // Update existing cost
    Route::put('/costs/{cost}', [FarmingCostController::class, 'updateCost'])
        ->name('costs.update');
    
    // Soft delete cost
    Route::delete('/costs/{cost}', [FarmingCostController::class, 'deleteCost'])
        ->name('costs.delete');
    
    // ============================================
    // COST CATEGORIES
    // ============================================
    
    // Get all cost categories
    Route::get('/categories', [FarmingCostController::class, 'getCategories'])
        ->name('categories.index');
    
    // ============================================
    // ML PREDICTIONS & ANALYSIS
    // ============================================
    
    // Get cost predictions for operation
    Route::get('/operations/{operation}/predict', [FarmingCostController::class, 'predictCosts'])
        ->name('operations.predict');
    
    // Get comprehensive cost analysis
    Route::get('/operations/{operation}/analysis', [FarmingCostController::class, 'costAnalysis'])
        ->name('operations.analysis');
    
    // Generate sample data for testing
    Route::post('/operations/{operation}/generate-sample-data', [FarmingCostController::class, 'generateSampleData'])
        ->name('operations.generate-sample');
    
    // ============================================
    // ML MODEL MANAGEMENT
    // ============================================
    
    // Train model for specific category
    Route::post('/categories/{category}/train', [FarmingCostController::class, 'trainModel'])
        ->name('categories.train');
    
    // Train all ML models
    Route::post('/ml/train-all-models', [FarmingCostController::class, 'trainAllModels'])
        ->name('ml.train-all');
    
    // Get ML model performance statistics
    Route::get('/ml/performance', [FarmingCostController::class, 'modelPerformance'])
        ->name('ml.performance');
});

/*
|--------------------------------------------------------------------------
| USAGE EXAMPLES
|--------------------------------------------------------------------------
*/

