<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BabengMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        switch ($role) {
            case 'reseller':
                Auth::shouldUse('reseller');

                if (Auth::guard('reseller')->check()) {
                    return $next($request);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Silahkan Login Terlebih Dahulu (Reseller)',
                ], 401);

            case 'adminOwner':
                Auth::shouldUse('api'); // default guard adminOwner

                if (Auth::check()) {
                    return $next($request);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Silahkan Login Terlebih Dahulu (Admin)',
                ], 401);

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Role tidak ditemukan',
                ], 401);
        }
    }
}
