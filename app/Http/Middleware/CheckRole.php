<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu untuk mengakses sistem.');
        }

        $userRole = auth()->user()->role;

        // Check if user's role is in the allowed roles list
        if (!in_array($userRole, $roles)) {
            abort(403, 'Akses Ditolak: Peran Anda (' . strtoupper($userRole) . ') tidak memiliki izin untuk halaman ini.');
        }

        return $next($request);
    }
}
