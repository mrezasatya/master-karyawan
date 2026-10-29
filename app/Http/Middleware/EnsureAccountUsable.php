<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Memastikan akun masih aktif di setiap permintaan dan memaksa ganti password
 * untuk akun baru/hasil reset sebelum fitur lain dapat dipakai.
 */
class EnsureAccountUsable
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->with('error', 'Akun Anda tidak aktif. Hubungi HR.');
        }

        if ($user && $user->must_change_password && ! $request->routeIs('password.change', 'password.update', 'logout')) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Ganti password terlebih dahulu.'], 403)
                : redirect()->route('password.change');
        }

        return $next($request);
    }
}
