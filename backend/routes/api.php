<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check (público)
Route::get('/health', function () {
    $checks = [];

    // Database
    try {
        DB::connection()->getPdo();
        $checks['database'] = ['status' => 'ok'];
    } catch (\Exception $e) {
        $checks['database'] = ['status' => 'error', 'message' => $e->getMessage()];
    }

    // Redis
    try {
        Redis::ping();
        $checks['redis'] = ['status' => 'ok'];
    } catch (\Exception $e) {
        $checks['redis'] = ['status' => 'error', 'message' => $e->getMessage()];
    }

    // MinIO (S3)
    try {
        Storage::disk('s3')->files();
        $checks['storage'] = ['status' => 'ok'];
    } catch (\Exception $e) {
        $checks['storage'] = ['status' => 'error', 'message' => $e->getMessage()];
    }

    $allHealthy = collect($checks)->every(fn($check) => $check['status'] === 'ok');

    return response()->json([
        'status' => $allHealthy ? 'healthy' : 'degraded',
        'service' => 'backend',
        'timestamp' => now()->toISOString(),
        'checks' => $checks,
    ], $allHealthy ? 200 : 503);
});

// Auth routes (SPA cookie-based con sesiones)
Route::middleware([
    'web',
    EnsureFrontendRequestsAreStateful::class,
])->group(function () {
    Route::post('/login', [LoginController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout']);
        Route::get('/user', [UserController::class, 'me']);
        
        // Admin only routes
        Route::middleware('role:admin')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
        });
    });
});
