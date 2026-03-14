<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotBanned
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isBanned()) {
            return $next($request);
        }

        if ($request->routeIs('banned') || $request->routeIs('logout')) {
            return $next($request);
        }

        return redirect()->route('banned');
    }
}
