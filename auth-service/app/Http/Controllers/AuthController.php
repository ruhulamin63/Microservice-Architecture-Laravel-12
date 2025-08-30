<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Helpers\JwtHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'User registration failed',
                'error' => $e->getMessage()
            ], 500);
        }

        // Sync with user-service
        try {
            Http::post('http://localhost:8001/api/users', [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
        } catch (\Exception $e) {
            // Log error but don't fail registration
            \Log::error('Failed to sync user with user-service: ' . $e->getMessage());
        }

        $token = JwtHelper::generateToken($user->id, $user->email);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
        ], 201);
    }

    /**
     * Login user and return JWT token
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = JwtHelper::generateToken($user->id, $user->email);

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Validate JWT token
     */
    public function validateToken(Request $request)
    {
        $token = JwtHelper::getTokenFromRequest($request);

        if (!$token) {
            return response()->json([
                'valid' => false,
                'message' => 'No token provided'
            ], 401);
        }

        $payload = JwtHelper::validateToken($token);

        if (!$payload) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid token'
            ], 401);
        }

        return response()->json([
            'valid' => true,
            'user' => [
                'id' => $payload['user_id'],
                'email' => $payload['email'],
            ]
        ]);
    }

    /**
     * Get current user profile
     */
    public function profile(Request $request)
    {
        $token = JwtHelper::getTokenFromRequest($request);

        if (!$token) {
            return response()->json([
                'message' => 'No token provided'
            ], 401);
        }

        $payload = JwtHelper::validateToken($token);

        if (!$payload) {
            return response()->json([
                'message' => 'Invalid token'
            ], 401);
        }

        $user = User::find($payload['user_id']);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }
}
