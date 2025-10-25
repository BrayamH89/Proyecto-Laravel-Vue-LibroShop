<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar que el usuario esté autenticado
        if (!auth()->check()) {
            return response()->json([
                'message' => 'No autenticado.'
            ], 401);
        }

        $user = auth()->user();
        
        // ✅ Verificar que role_id sea 1 (admin)
        if ($user->role_id != 1) {
            return response()->json([
                'message' => 'No tienes permisos de administrador.',
                'role_id_actual' => $user->role_id,
                'se_requiere' => 1
            ], 403);
        }

        return $next($request);
    }
}