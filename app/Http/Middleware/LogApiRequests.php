<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogApiRequests
{
    public function handle(Request $request, Closure $next)
    {
        // Log incoming request
        Log::info('API Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'route_name' => $request->route() ? $request->route()->getName() : null,
            'route_action' => $request->route() ? $request->route()->getActionName() : null,
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'headers' => [
                'authorization' => $request->header('Authorization') ? 'Bearer token present' : 'No auth header',
                'accept' => $request->header('Accept'),
                'content-type' => $request->header('Content-Type')
            ],
            'timestamp' => now()
        ]);

        $response = $next($request);

        // Log response
        Log::info('API Response', [
            'status_code' => $response->getStatusCode(),
            'url' => $request->fullUrl(),
            'response_size' => strlen($response->getContent()),
            'timestamp' => now()
        ]);

        return $response;
    }
}
