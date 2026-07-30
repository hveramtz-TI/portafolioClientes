<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

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
