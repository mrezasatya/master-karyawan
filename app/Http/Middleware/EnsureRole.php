<?php

namespace App\Http\Middleware;

use App\Support\Audit;
use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if ($request->user()?->role !== $role) {
            Audit::security('forbidden_route', ['path' => '/'.ltrim($request->path(), '/')]);
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
