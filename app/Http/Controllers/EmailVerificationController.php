<?php

namespace App\Http\Controllers;

use App\Models\WithdrawalVerificationCode;
use App\Notifications\WithdrawalEmailVerification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EmailVerificationController extends Controller
{
    /**
     * Send verification code to user's email
     */
    public function sendVerificationCode(Request $request): JsonResponse
    {
        try {
            // Add comprehensive logging for debugging
            Log::info('EmailVerificationController@sendVerificationCode called', [
                'headers' => $request->headers->all(),
                'bearer_token' => $request->bearerToken(),
                'has_authorization_header' => $request->hasHeader('Authorization'),
                'authorization_header' => $request->header('Authorization'),
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'all_request_data' => $request->all()
            ]);

            $user = Auth::user();

            Log::info('Authentication check result', [
                'user_found' => $user ? true : false,
                'user_id' => $user ? $user->id : null,
                'user_email' => $user ? $user->email : null,
                'auth_check' => Auth::check(),
                'auth_id' => Auth::id(),
                'auth_guard' => Auth::getDefaultDriver()
            ]);

            if (!$user) {
                Log::warning('User not authenticated in sendVerificationCode', [
                    'bearer_token' => $request->bearerToken(),
                    'headers' => $request->headers->all()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed. Please ensure you are logged in.',
                    'error' => 'USER_NOT_AUTHENTICATED',
                    'details' => [
                        'issue' => 'No authenticated user found for the provided token',
                        'solution' => 'Please login again to get a fresh token',
                        'token_status' => $request->bearerToken() ? 'Token provided but invalid/expired' : 'No token provided'
                    ]
                ], 401);
            }

            // Get user's IP and user agent for security tracking
            $ipAddress = $request->ip();
            $userAgent = $request->userAgent();

            // Create new verification code
            $verificationRecord = WithdrawalVerificationCode::createForUser($user, $ipAddress, $userAgent);

            // Send notification
            $user->notify(new WithdrawalEmailVerification($verificationRecord->code, 15));

            // Log the verification code sending
            Log::info('Withdrawal verification code sent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $ipAddress,
                'code_id' => $verificationRecord->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Verification code sent to your email',
                'data' => [
                    'email' => $this->maskEmail($user->email),
                    'expires_in_minutes' => 15,
                    'code_id' => $verificationRecord->id
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send verification code', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification code. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Verify the submitted verification code
     */
    public function verifyCode(Request $request): JsonResponse
    {
        // Add comprehensive logging for debugging
        Log::info('EmailVerificationController@verifyCode called', [
            'headers' => $request->headers->all(),
            'bearer_token' => $request->bearerToken(),
            'has_authorization_header' => $request->hasHeader('Authorization'),
            'authorization_header' => $request->header('Authorization'),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'all_request_data' => $request->all()
        ]);

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6|regex:/^[0-9]{6}$/'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid code format. Please enter a 6-digit number.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $code = $request->input('code');

            Log::info('Authentication check in verifyCode', [
                'user_found' => $user ? true : false,
                'user_id' => $user ? $user->id : null,
                'user_email' => $user ? $user->email : null,
                'auth_check' => Auth::check(),
                'auth_id' => Auth::id(),
                'auth_guard' => Auth::getDefaultDriver(),
                'code_being_verified' => $code
            ]);

            if (!$user) {
                Log::warning('User not authenticated in verifyCode', [
                    'bearer_token' => $request->bearerToken(),
                    'headers' => $request->headers->all(),
                    'code_attempted' => $code
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed. Please ensure you are logged in.',
                    'error' => 'USER_NOT_AUTHENTICATED',
                    'details' => [
                        'issue' => 'No authenticated user found for the provided token',
                        'solution' => 'Please login again to get a fresh token',
                        'token_status' => $request->bearerToken() ? 'Token provided but invalid/expired' : 'No token provided'
                    ]
                ], 401);
            }

            // Find valid verification code
            $verificationRecord = WithdrawalVerificationCode::findValidCode($user, $code);

            if (!$verificationRecord) {
                // Log failed verification attempt
                Log::warning('Invalid withdrawal verification code attempt', [
                    'user_id' => $user->id,
                    'attempted_code' => $code,
                    'ip_address' => $request->ip()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired verification code. Please request a new code.'
                ], 400);
            }

            // Mark code as used
            $verificationRecord->markAsUsed();

            // Generate temporary withdrawal token (valid for 5 minutes)
            $withdrawalToken = Str::random(64);
            $cacheKey = "withdrawal_verification_{$user->id}";
            
            Cache::put($cacheKey, [
                'token' => $withdrawalToken,
                'user_id' => $user->id,
                'verified_at' => now()->toISOString(),
                'ip_address' => $request->ip()
            ], 300); // 5 minutes

            // Log successful verification
            Log::info('Withdrawal verification code verified successfully', [
                'user_id' => $user->id,
                'code_id' => $verificationRecord->id,
                'ip_address' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Code verified successfully',
                'data' => [
                    'verification_token' => $withdrawalToken,
                    'expires_in_seconds' => 300,
                    'verified_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to verify code', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify code. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Check verification status
     */
    public function checkVerificationStatus(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $cacheKey = "withdrawal_verification_{$user->id}";
            $verificationData = Cache::get($cacheKey);

            if (!$verificationData) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active verification found',
                    'is_verified' => false
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Verification is active',
                'data' => [
                    'is_verified' => true,
                    'verified_at' => $verificationData['verified_at'],
                    'expires_in_seconds' => 300 // Fixed 5 minutes expiration
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to check verification status', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check verification status'
            ], 500);
        }
    }

    /**
     * Resend verification code (with rate limiting)
     */
    public function resendVerificationCode(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Rate limiting: Allow resend only every 60 seconds
            $rateLimitKey = "resend_verification_{$user->id}";

            if (Cache::has($rateLimitKey)) {
                $remainingTime = 60; // Fixed 60 seconds rate limit

                return response()->json([
                    'success' => false,
                    'message' => "Please wait {$remainingTime} seconds before requesting a new code",
                    'retry_after_seconds' => $remainingTime
                ], 429);
            }

            // Set rate limit
            Cache::put($rateLimitKey, true, 60); // 60 seconds

            // Send new verification code
            return $this->sendVerificationCode($request);

        } catch (\Exception $e) {
            Log::error('Failed to resend verification code', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to resend verification code'
            ], 500);
        }
    }

    /**
     * Mask email address for privacy
     */
    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        
        if (count($parts) !== 2) {
            return $email; // Return original if invalid format
        }

        $username = $parts[0];
        $domain = $parts[1];

        // Show first 2 characters and last 1 character of username
        $usernameLength = strlen($username);
        
        if ($usernameLength <= 3) {
            $maskedUsername = substr($username, 0, 1) . str_repeat('*', $usernameLength - 1);
        } else {
            $maskedUsername = substr($username, 0, 2) . str_repeat('*', $usernameLength - 3) . substr($username, -1);
        }

        return $maskedUsername . '@' . $domain;
    }

    /**
     * Clean up expired verification codes (can be called via cron)
     */
    public function cleanupExpiredCodes(): JsonResponse
    {
        try {
            $deletedCount = WithdrawalVerificationCode::cleanupExpired();
            
            Log::info('Cleaned up expired verification codes', [
                'deleted_count' => $deletedCount
            ]);

            return response()->json([
                'success' => true,
                'message' => "Cleaned up {$deletedCount} expired verification codes"
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired codes', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to cleanup expired codes'
            ], 500);
        }
    }
}
