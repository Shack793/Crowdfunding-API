<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\ContributionController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\Api\CampaignController as ApiCampaignController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WalletController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Include boost routes
require __DIR__.'/api-boost.php';

Route::prefix('v1')->group(function () {
    // Auth & Profile
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Public routes
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/payment-methods/public', [PaymentMethodController::class, 'publicIndex']);
    Route::post('/campaigns/{id}/donate/guest', [ContributionController::class, 'guestDonate']);
    Route::get('/campaigns/public', [CampaignController::class, 'getUserCampaigns']);
    Route::get('/campaigns/trending', [CampaignController::class, 'trending']);
    Route::get('/campaigns/{slug}/donations/recent', [ContributionController::class, 'recentDonations']);
    Route::get('/campaigns', [CampaignController::class, 'index']);
    Route::get('/campaigns/{slug}', [CampaignController::class, 'show']);
    
    // Public boost routes
    Route::get('/boost-plans', [\App\Http\Controllers\Api\BoostPlanController::class, 'index']);
    Route::get('/get-boosted-campaigns', [\App\Http\Controllers\Api\CampaignController::class, 'boosted']);

    

    Route::middleware('auth:sanctum')->group(function () {
        // Authenticated boost routes
        Route::post('/boost-campaign/{campaignId}', [\App\Http\Controllers\Api\BoostController::class, 'boostCampaign']);
        // User profile
        Route::get('/user', [AuthController::class, 'profile']);
        Route::put('/user/update', [AuthController::class, 'updateProfile']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // User dashboard
        Route::get('/userdashboard', [\App\Http\Controllers\UserDashboardController::class, 'index']);
        Route::get('/user/campaigns', [CampaignController::class, 'userCampaigns']);
        Route::get('/user/campaigns/{slug}', [CampaignController::class, 'showUserCampaign']);

        // Campaigns (CRUD & management)
        Route::post('/campaigns', [CampaignController::class, 'store']);
        Route::get('/campaigns/all', [CampaignController::class, 'getAllCampaigns']);
        Route::put('/campaigns/{slug}', [CampaignController::class, 'update']);
        Route::delete('/campaigns/{slug}', [CampaignController::class, 'destroy']);
        Route::post('/campaigns/{slug}/approve', [CampaignController::class, 'approve']);
        Route::post('/campaigns/{slug}/reject', [CampaignController::class, 'reject']);
        Route::post('/campaigns/{campaignId}/invite', [CampaignController::class, 'invite']);
        Route::post('/campaigns/invite/accept/{token}', [CampaignController::class, 'acceptInvite']);
        Route::delete('/campaigns/invite/{id}', [CampaignController::class, 'revokeInvite']);

        // Contributions
        Route::post('/campaigns/{slug}/donate', [ContributionController::class, 'authenticatedDonate']);
        Route::post('/campaigns/{slug}/contributions', [ContributionController::class, 'store']);
        Route::get('/contributions', [ContributionController::class, 'index']);
        Route::get('/contributions/{id}', [ContributionController::class, 'show']);
        Route::get('/get-contributions-stats', [ContributionController::class, 'contributionStats']);

        // Payment methods
        Route::get('/payment-methods', [PaymentMethodController::class, 'authenticatedIndex']);

        // Dashboard
        Route::get('/dashboard/campaigns', [DashboardController::class, 'campaigns']);
        Route::get('/dashboarduser/campaigns/{slug}/analytics', [DashboardController::class, 'campaignAnalytics']);
        Route::get('/dashboard/contributions', [DashboardController::class, 'contributions']);
        Route::get('/dashboard/withdrawals', [DashboardController::class, 'withdrawals']);

        // Rewards
        Route::post('/dashboard/rewards', [RewardController::class, 'store']);
        Route::put('/dashboard/rewards/{id}', [RewardController::class, 'update']);
        Route::delete('/dashboard/rewards/{id}', [RewardController::class, 'destroy']);

        // Withdrawals
        Route::post('/withdrawals', [WithdrawalController::class, 'store']);
        
    

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

        // Comments & Subscribers
        Route::get('/comments', [CommentController::class, 'index']);
        Route::get('/comments/stats', [CommentController::class, 'stats']);
        Route::post('/comments', [CommentController::class, 'store']);
        Route::post('/subscribe', [SubscriberController::class, 'store']);

        // Boosts
        Route::post('/campaigns/{campaign}/boost', [\App\Http\Controllers\Api\BoostController::class, 'store']);
        Route::get('/users/boosts', [\App\Http\Controllers\Api\BoostController::class, 'userBoosts']);
    });

    // Admin routes
    Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
        // Contribution approval
        Route::post('/contributions/{id}/approve-contribution', [
            \App\Http\Controllers\Admin\ContributionApprovalController::class,
            'approve-contribution'
        ]);
    });

    // Public boost routes
    //Route::get('/boost-plans', [\App\Http\Controllers\Api\BoostPlanController::class, 'index']);
   //Route::get('/campaigns/boosted', [\App\Http\Controllers\Api\CampaignController::class, 'boosted']);

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        // User boost history
        Route::get('/users/boosts', [\App\Http\Controllers\Api\BoostController::class, 'userBoosts']);
    });

    // Wallet & Payment routes
    Route::middleware('auth:sanctum')->group(function () {
        // Wallet routes
        Route::get('/wallet/balance', [WalletController::class, 'checkBalance']);
        Route::post('/wallet/update-after-withdrawal', [WalletController::class, 'updateWalletAfterWithdrawal']);
        
        // Payment routes
        Route::post('/payments/debit-wallet', [PaymentController::class, 'debitWallet']);
        Route::post('/payments/credit-wallet', [PaymentController::class, 'creditWallet']);
        Route::post('/payments/check-status', [PaymentController::class, 'checkStatus']);
    });
});
