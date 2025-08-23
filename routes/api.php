<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\CampaignItemController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MediaImageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\PriceRuleController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Public catalog routes for clients
Route::prefix('v1/catalog')->group(function () {
    Route::get('/media', [MediaController::class, 'catalog']);
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
    Route::prefix('v1/users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
        
        // Solo admin puede crear usuarios
        Route::middleware('admin')->post('/', [UserController::class, 'store']);
    });

    // Provider management routes
    Route::prefix('v1/providers')->group(function () {
        Route::get('/', [ProviderController::class, 'index']);
        Route::get('/{provider}', [ProviderController::class, 'show']);
        Route::put('/{provider}', [ProviderController::class, 'update']);
        Route::delete('/{provider}', [ProviderController::class, 'destroy']);
        
        // Solo admin puede crear proveedores
        Route::middleware('admin')->post('/', [ProviderController::class, 'store']);
    });

    // Campaign routes - accessible to all authenticated users
    Route::prefix('v1/campaigns')->group(function () {
        Route::get('/', [CampaignController::class, 'index']);
        Route::post('/', [CampaignController::class, 'store']);
        Route::get('/{campaign}', [CampaignController::class, 'show']);
        Route::put('/{campaign}', [CampaignController::class, 'update']);
        Route::delete('/{campaign}', [CampaignController::class, 'destroy']);
        Route::patch('/{campaign}/cancel', [CampaignController::class, 'cancel']);
    });

    // Campaign Items routes - accessible to all authenticated users
    Route::prefix('v1/campaign-items')->group(function () {
        Route::get('/', [CampaignItemController::class, 'index']);
        Route::post('/', [CampaignItemController::class, 'store']);
        Route::get('/{campaign_item}', [CampaignItemController::class, 'show']);
        Route::put('/{campaign_item}', [CampaignItemController::class, 'update']);
        Route::delete('/{campaign_item}', [CampaignItemController::class, 'destroy']);
        
        // Provider-only routes for accepting/rejecting campaign items
        Route::middleware('provider')->group(function () {
            Route::patch('/{campaign_item}/accept', [CampaignItemController::class, 'accept']);
            Route::patch('/{campaign_item}/reject', [CampaignItemController::class, 'reject']);
        });
    });

    // Payment routes - accessible to clients and admins
    Route::prefix('v1/payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::get('/{payment}', [PaymentController::class, 'show']);
        
        // Solo admin puede crear pagos
        Route::middleware('admin')->post('/', [PaymentController::class, 'store']);
    });

    // Payout routes - accessible to providers and admins
    Route::prefix('v1/payouts')->group(function () {
        Route::get('/', [PayoutController::class, 'index']);
        Route::get('/{payout}', [PayoutController::class, 'show']);
        
        // Solo admin puede crear payouts
        Route::middleware('admin')->post('/', [PayoutController::class, 'store']);
    });

    Route::middleware('admin.or.provider')->prefix('v1')->group(function () {
        
        // Media routes
        Route::prefix('media')->group(function () {
            Route::get('/', [MediaController::class, 'index']);
            Route::post('/', [MediaController::class, 'store']);
            Route::get('/{media}', [MediaController::class, 'show']);
            Route::put('/{media}', [MediaController::class, 'update']);
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
            Route::put('/{mediaImage}', [MediaImageController::class, 'update']);
            Route::delete('/{mediaImage}', [MediaImageController::class, 'destroy']);
        });

        // Price Rules routes
        Route::prefix('price-rules')->group(function () {
            Route::get('/', [PriceRuleController::class, 'index']);
            Route::post('/', [PriceRuleController::class, 'store']);
            Route::get('/{priceRule}', [PriceRuleController::class, 'show']);
            Route::put('/{priceRule}', [PriceRuleController::class, 'update']);
            Route::delete('/{priceRule}', [PriceRuleController::class, 'destroy']);
        });
    });
});
