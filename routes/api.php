<?php

use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AttributeOptionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MaterialStockController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\HeroSectionController;
use App\Http\Controllers\PaymentSettingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/heroes', [HeroSectionController::class, 'index']);

// Payment Methods — Public (no credentials, used by checkout UI)
Route::get('/payments/methods/{businessId}', [PaymentController::class, 'activeMethods']);

// Webhooks — Public routes, secured internally via per-gateway signature verification
Route::prefix('webhooks')->group(function () {
    Route::post('/stripe/{businessId}',  [WebhookController::class, 'handleStripe']);
    Route::post('/paypal/{businessId}',  [WebhookController::class, 'handlePaypal']);
    Route::post('/payhere/{businessId}', [WebhookController::class, 'handlePayhere']);
});
Route::get('/products/all', [\App\Http\Controllers\ProductController::class, 'index']);
Route::get('/products/{id}', [\App\Http\Controllers\ProductController::class, 'show']);
Route::get('/products/{id}/variants', [\App\Http\Controllers\ProductController::class, 'getVariants']);
Route::get('/products/{id}/required-attributes', [\App\Http\Controllers\ProductController::class, 'getRequiredAttributes']);
Route::get('/categories/all', [CategoryController::class, 'index']);


// Health Check
Route::get('/health', [HealthController::class, 'check']);

// Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [\App\Http\Controllers\AuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login']);
});

// Customer Authentication Routes
Route::prefix('customer')->group(function () {
    Route::post('/auth/register', [\App\Http\Controllers\CustomerAuthController::class, 'register']);
    Route::post('/auth/login', [\App\Http\Controllers\CustomerAuthController::class, 'login']);

    // Public Customer Routes
    Route::post('/orders', [\App\Http\Controllers\OrderController::class, 'customerStore']);

    // Protected Customer Routes
    Route::middleware('jwt.verify:customer')->group(function () {
        Route::get('/profile', [\App\Http\Controllers\CustomerAuthController::class, 'profileContent']);
        Route::put('/profile', [\App\Http\Controllers\CustomerAuthController::class, 'updateProfile']);
        
        // Cart Routes
        Route::get('/cart', [\App\Http\Controllers\CartController::class, 'index']);
        Route::post('/cart/sync', [\App\Http\Controllers\CartController::class, 'sync']);
        Route::post('/cart/items', [\App\Http\Controllers\CartController::class, 'store']);
        Route::delete('/cart/items/{id}', [\App\Http\Controllers\CartController::class, 'destroy']);
        Route::delete('/cart', [\App\Http\Controllers\CartController::class, 'clear']);

        // Customer Orders
        Route::get('/orders', [\App\Http\Controllers\OrderController::class, 'customerOrders']);
    });
});

// Protected Routes - Require JWT Authentication
Route::middleware('jwt.verify')->group(function () {
    
    // Profile Management
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'index']);
        Route::put('/personal', [ProfileController::class, 'updatePersonal']);
        Route::put('/company', [ProfileController::class, 'updateCompany']);
        Route::put('/password', [ProfileController::class, 'changePassword']);
    });

    // Attributes Management
    Route::prefix('attributes')->group(function () {
        Route::get('/all', [AttributeController::class, 'index']);
        Route::post('/create', [AttributeController::class, 'store']);
        Route::put('/update', [AttributeController::class, 'update']);
        Route::delete('/delete', [AttributeController::class, 'destroy']);

        // Attribute Options
        Route::prefix('options')->group(function () {
            Route::get('/all', [AttributeOptionController::class, 'index']);
            Route::post('/create', [AttributeOptionController::class, 'store']);
            Route::put('/update', [AttributeOptionController::class, 'update']);
            Route::delete('/delete', [AttributeOptionController::class, 'destroy']);
        });
    });

    // Materials Management
    Route::prefix('materials')->group(function () {
        Route::get('/all', [MaterialController::class, 'index']);
        Route::post('/create', [MaterialController::class, 'store']);
        Route::put('/update', [MaterialController::class, 'update']);
        Route::delete('/delete', [MaterialController::class, 'destroy']);
    });

    // Material Stocks Management
    Route::prefix('material-stocks')->group(function () {
        Route::get('/all', [MaterialStockController::class, 'index']);
        Route::post('/create', [MaterialStockController::class, 'store']);
        Route::get('/show/{materialStock}', [MaterialStockController::class, 'show']);
        Route::put('/update', [MaterialStockController::class, 'update']);
        Route::delete('/delete', [MaterialStockController::class, 'destroy']);
    });

    // Categories Management
    Route::prefix('categories')->group(function () {
        Route::post('/create', [CategoryController::class, 'store']);
        Route::put('/update', [CategoryController::class, 'update']);
        Route::delete('/delete', [CategoryController::class, 'destroy']);
    });

    // Product Templates Management
    Route::prefix('product-templates')->group(function () {
        Route::get('/all', [\App\Http\Controllers\ProductTemplateController::class, 'index']);
        Route::post('/create', [\App\Http\Controllers\ProductTemplateController::class, 'store']);
        Route::put('/update', [\App\Http\Controllers\ProductTemplateController::class, 'update']);
        Route::delete('/delete', [\App\Http\Controllers\ProductTemplateController::class, 'destroy']);
    });

    // Product Stocks Management
    Route::prefix('product-stocks')->group(function () {
        Route::get('/all', [\App\Http\Controllers\ProductStockController::class, 'index']);
        Route::post('/create', [\App\Http\Controllers\ProductStockController::class, 'store']);
        Route::delete('/delete', [\App\Http\Controllers\ProductStockController::class, 'destroy']);
    });

    // Products Management (Final Sellable Items)
    Route::prefix('products')->group(function () {
        Route::post('/create', [\App\Http\Controllers\ProductController::class, 'store']);
        Route::put('/update', [\App\Http\Controllers\ProductController::class, 'update']);
        Route::delete('/delete', [\App\Http\Controllers\ProductController::class, 'destroy']);
    });

    // Orders Management
    Route::prefix('orders')->group(function () {
        Route::get('/all', [OrderController::class, 'index']);
        Route::get('/get', [OrderController::class, 'show']);
        Route::post('/create', [OrderController::class, 'store']);
        Route::put('/update-status', [OrderController::class, 'updateStatus']);
        Route::delete('/delete', [OrderController::class, 'destroy']);
    });

    // Order Statuses Management
    Route::prefix('order-statuses')->group(function () {
        Route::get('/all', [OrderStatusController::class, 'index']);
        Route::post('/create', [OrderStatusController::class, 'store']);
        Route::put('/update', [OrderStatusController::class, 'update']);
        Route::delete('/delete', [OrderStatusController::class, 'destroy']);
    });

    // Hero Sections Management
    Route::prefix('heroes')->group(function () {
        Route::post('/create', [HeroSectionController::class, 'store']);
        Route::put('/update/{id}', [HeroSectionController::class, 'update']);
        Route::delete('/delete/{id}', [HeroSectionController::class, 'destroy']);
    });

    // Media Management
    Route::post('/media/upload', [\App\Http\Controllers\MediaController::class, 'upload']);

    // Dashboard
    Route::get('/dashboard/stats', [\App\Http\Controllers\DashboardController::class, 'stats']);

    // Payment Settings Management (Admin)
    Route::prefix('payment-settings')->group(function () {
        Route::get('/',          [PaymentSettingController::class, 'index']);
        Route::post('/save',     [PaymentSettingController::class, 'save']);
        Route::patch('/{id}/toggle', [PaymentSettingController::class, 'toggle']);
        Route::delete('/{id}',   [PaymentSettingController::class, 'destroy']);
    });
});

// Payment Initiation & Verification (Customer JWT)
Route::middleware('jwt.verify:customer')->prefix('payments')->group(function () {
    Route::post('/initiate', [PaymentController::class, 'initiate']);
    Route::get('/verify',    [PaymentController::class, 'verify']);
});
