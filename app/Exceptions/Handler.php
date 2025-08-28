<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Handle unauthenticated users.
     */
    protected function unauthenticated($request, \Illuminate\Auth\AuthenticationException $exception)
    {
        // Enhanced error response for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            $bearerToken = $request->bearerToken();
            $authHeader = $request->header('Authorization');
            
            // Try to get more token information for debugging
            $tokenInfo = $this->analyzeToken($bearerToken);
            
            // Log authentication failure for debugging
            \Illuminate\Support\Facades\Log::warning('Authentication failed', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'has_bearer_token' => !empty($bearerToken),
                'bearer_token_length' => $bearerToken ? strlen($bearerToken) : 0,
                'authorization_header' => $authHeader ? 'Present' : 'Missing',
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'guard' => $exception->guards(),
                'exception_message' => $exception->getMessage(),
                'token_analysis' => $tokenInfo
            ]);

            $errorData = [
                'success' => false,
                'message' => 'Authentication required to access this resource.',
                'error' => 'UNAUTHENTICATED',
                'details' => [
                    'issue' => $tokenInfo['likely_issue'] ?? 'No valid authentication token provided',
                    'required' => 'Bearer token in Authorization header',
                    'format' => 'Authorization: Bearer <your-token>'
                ]
            ];

            // Add debugging info if in debug mode
            if (config('app.debug')) {
                $errorData['debug'] = [
                    'bearer_token_provided' => !empty($bearerToken),
                    'token_length' => $bearerToken ? strlen($bearerToken) : 0,
                    'authorization_header' => $authHeader ? 'Present' : 'Missing',
                    'guards_attempted' => $exception->guards(),
                    'request_url' => $request->fullUrl(),
                    'token_analysis' => $tokenInfo
                ];
            }

            return response()->json($errorData, 401);
        }

        // Fallback for non-API requests
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    /**
     * Analyze the bearer token to provide better debugging information
     */
    private function analyzeToken($token)
    {
        if (!$token) {
            return [
                'status' => 'missing',
                'likely_issue' => 'No bearer token provided'
            ];
        }

        try {
            // Check if token exists in database
            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            
            if (!$personalAccessToken) {
                return [
                    'status' => 'not_found',
                    'likely_issue' => 'Token not found in database - may be invalid or manually deleted',
                    'token_length' => strlen($token)
                ];
            }

            // Check if token is expired
            if ($personalAccessToken->expires_at && $personalAccessToken->expires_at->isPast()) {
                return [
                    'status' => 'expired',
                    'likely_issue' => 'Token has expired',
                    'expired_at' => $personalAccessToken->expires_at->toISOString(),
                    'token_name' => $personalAccessToken->name
                ];
            }

            // Check if user still exists
            if (!$personalAccessToken->tokenable) {
                return [
                    'status' => 'user_deleted',
                    'likely_issue' => 'User account associated with token no longer exists',
                    'token_name' => $personalAccessToken->name
                ];
            }

            return [
                'status' => 'valid_but_failed',
                'likely_issue' => 'Token appears valid but authentication failed - possible middleware or guard configuration issue',
                'token_name' => $personalAccessToken->name,
                'user_id' => $personalAccessToken->tokenable_id,
                'created_at' => $personalAccessToken->created_at->toISOString()
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'analysis_failed',
                'likely_issue' => 'Could not analyze token',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
