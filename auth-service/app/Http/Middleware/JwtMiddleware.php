<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\JwtHelper;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = JwtHelper::getTokenFromRequest($request);

        if (!$token) {
            return response()->json([
                'message' => 'No token provided',
                'error' => 'UNAUTHORIZED'
            ], 401);
        }

        $payload = JwtHelper::validateToken($token);

        if (!$payload) {
            return response()->json([
                'message' => 'Invalid or expired token',
                'error' => 'UNAUTHORIZED'
            ], 401);
        }

        // Add user info to request for use in controllers
        $request->merge(['jwt_user' => [
            'id' => $payload['user_id'],
            'email' => $payload['email']
        ]]);

        return $next($request);
    }
}
