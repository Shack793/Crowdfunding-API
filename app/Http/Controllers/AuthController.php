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
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'phone' => 'sometimes|string',
                'country' => 'sometimes|string',
                'profile_image' => 'sometimes|mimes:jpg,jpeg,png,gif|max:2048',
                'password' => 'sometimes|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            if ($request->has('name')) $user->name = $request->name;
            if ($request->has('phone')) $user->phone = $request->phone;
            if ($request->has('country')) $user->country = $request->country;
            if ($request->has('profile_image')) $user->profile_image = $request->profile_image;
            if ($request->has('password')) $user->password = Hash::make($request->password);

            $user->save();

            DB::commit();

            return response()->json([
                'user' => $user,
                'message' => 'Profile updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Profile update error', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id
            ]);
            return response()->json(['message' => 'Profile update failed'], 500);
        }
    }
}
