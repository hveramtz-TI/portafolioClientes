<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\RubroController;
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

        // Clients
        Route::patch('clients/{client}/status', [ClientController::class, 'updateStatus']);
        Route::apiResource('clients', ClientController::class)->only(['index', 'store', 'update', 'destroy']);

        // Companies
        Route::apiResource('companies', CompanyController::class)->only(['index', 'store', 'update', 'show', 'destroy']);
        
        // Admin only routes
        Route::middleware('role:admin')->group(function () {
            Route::get('/users', [UserController::class, 'index']);

            // Base catalog (rubros)
            Route::get('/rubros/{rubro}/categorias', [RubroController::class, 'categorias']);
            Route::patch('/rubros/{rubro}/deactivate', [RubroController::class, 'deactivate']);
            Route::patch('/rubros/{rubro}/reactivate', [RubroController::class, 'reactivate']);
            Route::apiResource('rubros', RubroController::class)->only(['index', 'store', 'update', 'destroy']);

            // Base catalog (categorias)
            Route::get('/categorias/{categoria}/services', [CategoriaController::class, 'services']);
            Route::patch('/categorias/{categoria}/deactivate', [CategoriaController::class, 'deactivate']);
            Route::patch('/categorias/{categoria}/reactivate', [CategoriaController::class, 'reactivate']);
            Route::apiResource('categorias', CategoriaController::class)->only(['index', 'store', 'update', 'destroy']);
        });
    });
});
