<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BabengMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $role)
    {
        if ($role === 'reseller') {
            Auth::shouldUse('reseller');

            $authHeader = $request->header('Authorization');
            $token = null;

            if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $token = $matches[1];
            }

            if (Auth::guard('reseller')->check()) {
                return $next($request);
            }

            return response()->json([
                'success' => false,
                'message' => 'Silahkan Login Terlebih Dahulu (Reseller)',
                'token'   => $token ?? 'Token tidak ditemukan',
                'header'  => $authHeader,
            ], 401);
        }
        if ($role === 'adminOwner') {
            if (Auth::guard()->user()) {

                return $next($request);
            } else {
                return response()->json([
                    'success'    => false,
                    'message'    => 'Silahkan Login Terlebih Dahulu',
                ], 401);
            }
        } else {
            return response()->json([
                'success'    => false,
                'message'    => 'Role tidak ditemukan',
            ], 401);
        }
    }
}
