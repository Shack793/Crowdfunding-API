<?php

use App\Http\Controllers\Api\BoostController;
use App\Http\Controllers\Api\BoostPlanController;
use App\Http\Controllers\Api\CampaignController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Boost API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/boost-plans', [BoostPlanController::class, 'index']);
Route::get('/campaigns/boosted', [CampaignController::class, 'boosted']);

// Authenticated user routes
Route::middleware('auth:sanctum')->group(function () {
    // Boost management
    Route::post('/campaigns/{campaign}/boost', [BoostController::class, 'store']);
    Route::get('/users/boosts', [BoostController::class, 'userBoosts']);
    
    // Admin routes
    Route::middleware('admin')->group(function () {
        Route::get('/admin/boosts', [BoostController::class, 'index']);
        Route::get('/admin/boost-stats', [BoostController::class, 'stats']);
    });
});
