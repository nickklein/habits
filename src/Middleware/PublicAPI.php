<?php

namespace NickKlein\Habits\Middleware;

use Closure;
use Illuminate\Http\Request;

class PublicAPI
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (empty($request->token)) {
            abort(404);
        }

        if (empty(config('auth.public_token'))) {
            abort(404);
        }

        if ($request->token !== config('auth.public_token')) {
            abort(404);
        }

        return $next($request);
    }
}
