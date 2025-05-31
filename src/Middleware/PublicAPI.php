<?php

namespace NickKlein\Habits\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        // NOTE: Danger.
        return $next($request);

        $authorizationHeader = $request->header('Authorization');
        if (empty($authorizationHeader) || !preg_match('/^Bearer\s(\S+)$/', $authorizationHeader, $matches)) {
            return abort(404, 'Unauthorized: Bearer token is missing or invalid.');
        }
        // Extract Bearer token if it's valid
        $token = $matches[1];

        // Validate the token
        if (!$this->isValidToken($token)) {
            return abort(404, 'Token not found or invalid.');
        }

        return $next($request);
    }

    private function isValidToken(string $requestToken): bool
    {
        // If config is empty, return false, maybe some misconfig
        if (empty(config('auth.public_token'))) {
            Log::warning('The public_token configuration is missing.');
            return false;
        }

        // If the token doesn't match the config, then return false.
        if ($requestToken !== config('auth.public_token')) {
            return false;
        }

        return true;
    }
}
