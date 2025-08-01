<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'sometimes|string',
            'country' => 'sometimes|string',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:admin,institution,individual',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone ?? null,
                'country' => $request->country ?? null,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'user' => $user,
                'token' => $token,
                'message' => 'Registration successful'
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'data' => $request->except(['password', 'password_confirmation'])
            ]);
            return response()->json(['message' => 'Registration failed'], 500);
        }
    }

    public function login(Request $request)
    {
        Log::info('Login attempt', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'request_data' => $request->except(['password'])
        ]);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::error('Login validation failed', [
                'errors' => $validator->errors()->toArray()
            ]);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                Log::error('Login failed for user: ' . $request->email, [
                    'email' => $request->email,
                    'ip' => $request->ip(),
                    'user_agent' => $request->header('User-Agent')
                ]);
                return response()->json(['message' => 'Invalid credentials'], 401);
            }
            
            Log::info('User lookup result', [
                'user_found' => !is_null($user),
                'email' => $request->email
            ]);

            // Revoke all existing tokens
            $user->tokens()->delete();

            // Create new token
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('Login successful', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'user' => $user,
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            Log::error('Login error', [
                'error' => $e->getMessage(),
                'email' => $request->email,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Login failed'], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Logged out successfully']);
        } catch (\Exception $e) {
            Log::error('Logout error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id
            ]);
            return response()->json(['message' => 'Logout failed'], 500);
        }
    }

    public function profile(Request $request)
    {
        try {
            return response()->json(['user' => $request->user()]);
        } catch (\Exception $e) {
            Log::error('Profile fetch error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id
            ]);
            return response()->json(['message' => 'Failed to fetch profile'], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = $request->user();
            
            // Handle both JSON and form data
            $requestData = [];
            if ($request->isJson() || $request->header('Content-Type') === 'application/json') {
                $requestData = $request->json()->all();
            } else {
                // Try to get JSON data even if content-type is wrong
                $jsonData = $request->json();
                if ($jsonData && $jsonData->all()) {
                    $requestData = $jsonData->all();
                } else {
                    $requestData = $request->all();
                }
            }
            
            // Debug: Log all request information
            Log::info('Full request debug', [
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'is_json' => $request->isJson(),
                'all_data' => $request->all(),
                'json_data' => $request->json() ? $request->json()->all() : null,
                'processed_data' => $requestData,
                'raw_content' => $request->getContent()
            ]);
            
            $validator = Validator::make($requestData, [
                'name' => 'sometimes|string|max:255',
                'firstName' => 'sometimes|string|max:255',
                'lastName' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'phone' => 'sometimes|string|nullable',
                'country' => 'sometimes|string|nullable',
                'profile_image' => 'sometimes|mimes:jpg,jpeg,png,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [];
            $hasChanges = false;

            // Log the incoming request data for debugging
            Log::info('Profile update request data', [
                'user_id' => $user->id,
                'request_data' => $requestData,
                'current_user_data' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'country' => $user->country,
                    'profile_image' => $user->profile_image
                ]
            ]);

            // Handle name field - either direct name or firstName + lastName
            if (isset($requestData['firstName']) || isset($requestData['lastName'])) {
                $firstName = $requestData['firstName'] ?? '';
                $lastName = $requestData['lastName'] ?? '';
                $newName = trim($firstName . ' ' . $lastName);
                
                Log::info('Name comparison', [
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'newName' => $newName,
                    'currentName' => $user->name,
                    'are_different' => ($newName !== $user->name)
                ]);
                
                if ($newName !== $user->name && !empty(trim($newName))) {
                    $updateData['name'] = $newName;
                    $hasChanges = true;
                }
            } elseif (isset($requestData['name'])) {
                $newName = $requestData['name'];
                
                Log::info('Direct name comparison', [
                    'newName' => $newName,
                    'currentName' => $user->name,
                    'are_different' => ($newName !== $user->name)
                ]);
                
                if ($newName !== $user->name && !empty(trim($newName))) {
                    $updateData['name'] = $newName;
                    $hasChanges = true;
                }
            }

            // Check other fields for changes
            if (isset($requestData['email']) && $requestData['email'] !== $user->email) {
                $updateData['email'] = $requestData['email'];
                $hasChanges = true;
                Log::info('Email change detected', [
                    'old' => $user->email,
                    'new' => $requestData['email']
                ]);
            }

            if (isset($requestData['phone']) && $requestData['phone'] !== $user->phone) {
                $updateData['phone'] = $requestData['phone'];
                $hasChanges = true;
                Log::info('Phone change detected', [
                    'old' => $user->phone,
                    'new' => $requestData['phone']
                ]);
            }

            if (isset($requestData['country']) && $requestData['country'] !== $user->country) {
                $updateData['country'] = $requestData['country'];
                $hasChanges = true;
                Log::info('Country change detected', [
                    'old' => $user->country,
                    'new' => $requestData['country']
                ]);
            }

            if (isset($requestData['profile_image']) && $requestData['profile_image'] !== $user->profile_image) {
                $updateData['profile_image'] = $requestData['profile_image'];
                $hasChanges = true;
                Log::info('Profile image change detected', [
                    'old' => $user->profile_image,
                    'new' => $requestData['profile_image']
                ]);
            }

            Log::info('Update analysis', [
                'hasChanges' => $hasChanges,
                'updateData' => $updateData
            ]);

            if (!$hasChanges) {
                return response()->json([
                    'success' => true,
                    'user' => $user->fresh(), // Get fresh user data
                    'message' => 'No changes detected',
                    'debug' => [
                        'request_method' => $request->method(),
                        'content_type' => $request->header('Content-Type'),
                        'processed_data' => $requestData
                    ]
                ]);
            }

            // Update user with only changed fields
            $user->update($updateData);

            DB::commit();

            Log::info('Profile updated successfully', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($updateData),
                'changes' => $updateData
            ]);

            return response()->json([
                'success' => true,
                'user' => $user->fresh(), // Get fresh user data with updated timestamp
                'message' => 'Profile updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Profile update error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Profile update failed'
            ], 500);
        }
    }

    public function updatePassword(Request $request)
    {
        try {
            DB::beginTransaction();

            $user = $request->user();
            $validator = Validator::make($request->all(), [
                'currentPassword' => 'required|string',
                'newPassword' => 'required|string|min:6',
                'confirmPassword' => 'required|string|same:newPassword',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify current password
            if (!Hash::check($request->input('currentPassword'), $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect',
                    'errors' => ['currentPassword' => ['The current password is incorrect']]
                ], 422);
            }

            // Update password
            $user->password = Hash::make($request->input('newPassword'));
            $user->save();

            // Revoke all existing tokens except current one for security
            $currentToken = $request->user()->currentAccessToken();
            $user->tokens()->where('id', '!=', $currentToken->id)->delete();

            DB::commit();

            Log::info('Password updated successfully', [
                'user_id' => $user->id,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Password update error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Password update failed'
            ], 500);
        }
    }
}
