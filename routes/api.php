<?php

use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConfigurationController;
use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\ModelController;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Support\Facades\Route;

// Public site (cached reads)
Route::get('/models', [ModelController::class, 'index']);
Route::get('/models/{slug}', [ModelController::class, 'show']);
Route::get('/options', [ModelController::class, 'options']);
Route::post('/configurations/calculate', [ConfigurationController::class, 'calculate'])->middleware('throttle:pricing');

// Public writes (rate limited per client, see AppServiceProvider)
Route::middleware('throttle:public-writes')->group(function () {
    Route::post('/configurations', [ConfigurationController::class, 'store']);
    Route::post('/inquiries', [InquiryController::class, 'store']);
});

// Admin authentication (bearer tokens via Sanctum)
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Management: admin and editor roles only
    Route::middleware(EnsureStaff::class)->prefix('admin')->group(function () {
        Route::get('/dashboard', Admin\DashboardController::class);
        Route::apiResource('models', Admin\ModelController::class)->parameters(['models' => 'carModel']);
        Route::get('/models/{carModel}/images', [Admin\ImageController::class, 'index']);
        Route::post('/models/{carModel}/images', [Admin\ImageController::class, 'store']);
        Route::put('/models/{carModel}/images/order', [Admin\ImageController::class, 'reorder']);
        Route::delete('/images/{image}', [Admin\ImageController::class, 'destroy']);
        Route::apiResource('options', Admin\OptionController::class)->except('show');
        Route::apiResource('inquiries', Admin\InquiryController::class)->only(['index', 'show', 'update']);
    });
});
