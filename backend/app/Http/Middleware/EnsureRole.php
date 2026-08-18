<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * Verifica que el usuario autenticado tenga el rol requerido.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  $role  El rol requerido (ej: 'admin')
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!$request->user() || $request->user()->role !== $role) {
            return response()->json([
                'message' => 'Forbidden. Required role: ' . $role,
            ], 403);
        }

        return $next($request);
    }
}
