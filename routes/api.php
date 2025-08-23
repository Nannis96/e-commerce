<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CampaignItemController;
use App\Http\Controllers\CancellationController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MediaImageController;
use App\Http\Controllers\PriceRuleController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Protected auth routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::get('/profile', [AuthController::class, 'profile']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
    
    // Original user route
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // User management routes
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
    //     Route::post('/', [UserController::class, 'store']);
    //     Route::get('/{user}', [UserController::class, 'show']);
    //     Route::put('/{user}', [UserController::class, 'update']);
    //     Route::delete('/{user}', [UserController::class, 'destroy']);
    });

    // Campaign routes - accessible to all authenticated users
    Route::prefix('campaigns')->group(function () {
        Route::get('/', [CampaignController::class, 'index']);
        Route::post('/', [CampaignController::class, 'store']);
        Route::get('/{campaign}', [CampaignController::class, 'show']);
        Route::patch('/{campaign}', [CampaignController::class, 'update']);
        Route::delete('/{campaign}', [CampaignController::class, 'destroy']);
    });

    // Campaign Items routes - accessible to all authenticated users
    Route::prefix('campaign-items')->group(function () {
        Route::get('/', [CampaignItemController::class, 'index']);
        Route::post('/', [CampaignItemController::class, 'store']);
        Route::get('/{campaign_item}', [CampaignItemController::class, 'show']);
        Route::patch('/{campaign_item}', [CampaignItemController::class, 'update']);
        Route::delete('/{campaign_item}', [CampaignItemController::class, 'destroy']);
    });

    Route::middleware('admin.or.provider')->prefix('v1')->group(function () {
        
        // Media routes
        Route::prefix('media')->group(function () {
            Route::get('/', [MediaController::class, 'index']);
            Route::post('/', [MediaController::class, 'store']);
            Route::get('/{media}', [MediaController::class, 'show']);
            Route::patch('/{media}', [MediaController::class, 'update']);
            Route::delete('/{media}', [MediaController::class, 'destroy']);
            
            // Media images
            Route::get('/{media}/images', [MediaImageController::class, 'getByMedia']);
            
            // Media price rules
            Route::get('/{media}/price-rules', [MediaController::class, 'getPriceRules']);
            Route::get('/{media}/price-rules/active', [MediaController::class, 'getActivePriceRules']);
            Route::post('/{media}/price-rules/attach', [MediaController::class, 'attachPriceRules']);
            Route::post('/{media}/price-rules/detach', [MediaController::class, 'detachPriceRules']);
            Route::post('/{media}/price-rules/sync', [MediaController::class, 'syncPriceRules']);
            Route::get('/{media}/calculate-price', [MediaController::class, 'calculatePrice']);
        });

        // Media Images routes
        Route::prefix('media-images')->group(function () {
            Route::get('/', [MediaImageController::class, 'index']);
            Route::post('/', [MediaImageController::class, 'store']);
            Route::get('/{mediaImage}', [MediaImageController::class, 'show']);
            Route::patch('/{mediaImage}', [MediaImageController::class, 'update']);
            Route::delete('/{mediaImage}', [MediaImageController::class, 'destroy']);
        });

        // Price Rules routes
        Route::prefix('price-rules')->group(function () {
            Route::get('/', [PriceRuleController::class, 'index']);
            Route::post('/', [PriceRuleController::class, 'store']);
            Route::get('/{priceRule}', [PriceRuleController::class, 'show']);
            Route::patch('/{priceRule}', [PriceRuleController::class, 'update']);
            Route::delete('/{priceRule}', [PriceRuleController::class, 'destroy']);
        });

        // Cancellations routes
        Route::prefix('cancellations')->group(function () {
            Route::get('/', [CancellationController::class, 'index']);
            Route::post('/', [CancellationController::class, 'store']);
            Route::get('/{cancellation}', [CancellationController::class, 'show']);
            Route::patch('/{cancellation}', [CancellationController::class, 'update']);
            Route::delete('/{cancellation}', [CancellationController::class, 'destroy']);
        });
    });
});
