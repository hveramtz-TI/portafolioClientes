<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);

        $middleware->web(append: [
            VerifyCsrfToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // Single render map for the user-catalog surface (D-3, R7): the
        // engine's duplicate signal and the partial-index race both become the
        // same 409 JSON shape, so no duplicate path can surface as a 500.
        $exceptions->render(function (DomainException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json(['message' => $exception->getMessage()], 409);
        });

        $exceptions->render(function (QueryException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            // JD4-3: only the user-catalog fork duplicate becomes this 409.
            // PostgreSQL: SQLSTATE 23505 covers every unique violation, so the
            // branch is scoped by the constraint the migration created
            // (2026_09_14_000000: user_catalog_items_live_identity_unique),
            // which the driver message quotes. SQLite keeps its message-scoped
            // match. Anything else falls through to the default handler.
            $sqlState = $exception->errorInfo[0] ?? $exception->getCode();
            $isDuplicate = ($sqlState === '23505'
                    && str_contains($exception->getMessage(), 'user_catalog_items_live_identity_unique'))
                || ($sqlState === '23000' && str_contains($exception->getMessage(), 'UNIQUE constraint failed: user_catalog_items'));

            if (! $isDuplicate) {
                return null;
            }

            return response()->json([
                'message' => 'You already have a live fork of this base item.',
            ], 409);
        });
    })->create();
