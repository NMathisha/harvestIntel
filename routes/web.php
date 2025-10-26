<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\costController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\operationController;
use App\Http\Controllers\FarmingCostController;
use App\Http\Controllers\costCategoryController;
use App\Http\Controllers\trainingViewController;
use App\Http\Controllers\costPredictionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
    //   return 'hi1234344';
});


Route::get('/dashboard/stats', [FarmingCostController::class, 'dashboardStats'])
    ->name('dashboard.stats');

// ============================================
// FARMING OPERATIONS MANAGEMENT
// ============================================

// List all operations (with filters)
Route::get('/operations', [FarmingCostController::class, 'getOperations'])
    ->name('operations.index');

// Get available operations for selection
Route::get('/operations/available', [operationController::class, 'getAvailableOperations'])
    ->name('operations.available');

// Compare multiple operations
Route::get('/operations/compare', [FarmingCostController::class, 'compareOperations'])->name('operations.compare');


// Create new operation
Route::post('/operation/create', [FarmingCostController::class, 'createFarmingOperation'])
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

Route::get('/ope/{id}', [FarmingCostController::class, 'getOperationById'])
    ->name('operations.get');


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
Route::get('/categories/{category}/train', [FarmingCostController::class, 'trainModel'])
    ->name('categories.train');

// Train all ML models
Route::post('/ml/train-all-models', [FarmingCostController::class, 'trainAllModels'])
    ->name('ml.train-all');

// Get ML model performance statistics
Route::get('/ml/performance', [FarmingCostController::class, 'modelPerformance'])
    ->name('ml.performance');


Route::get('/getCosts', [costController::class, 'index'])->name('getCosts');
Route::get('/getCost/{id}',[costController::class,'getCost']);

Route::get('/getOpeCost', [operationController::class, 'getOpeCost'])->name('getOpeCost');

Route::get('/costPredict', [costPredictionController::class, 'index'])->name('costPredict');

Route::get('/costAnalisis',[costPredictionController::class,'showAnalisis'])->name('costAnalisis');

Route::get('/categoryTrain',[trainingViewController::class,'showCategory'])->name('categoryTrain');


Route::get('/home', function () {
    return view('pages.home');
})->name('home');


Route::get('/profile', function () {
    return view('pages.profile');
})->name('profile');


Route::get('login', [AuthController::class, 'index'])->name('login');

Route::post('post-login', [AuthController::class, 'postLogin'])->name('login.post');

Route::get('registration', [AuthController::class, 'registration'])->name('register');

Route::post('post-registration', [AuthController::class, 'postRegistration'])->name('register.post');

Route::get('dashboard', [AuthController::class, 'dashboard']);

Route::get('logout', [AuthController::class, 'logout'])->name('logout');
