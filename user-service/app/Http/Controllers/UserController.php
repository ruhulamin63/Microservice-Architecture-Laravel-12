<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Helpers\JwtHelper;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     * Requires authentication
     */
    public function index(Request $request)
    {
        // Only authenticated users can list users
        $currentUser = JwtHelper::getUserFromToken($request);

        if (!$currentUser) {
            return response()->json([
                'message' => 'Authentication required'
            ], 401);
        }

        return response()->json(User::all(['id', 'name', 'email']));
    }

    /**
     * Store a newly created resource in storage.
     * This should be called by auth-service, not directly
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json($user, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $currentUser = JwtHelper::getUserFromToken($request);

        // Allow viewing own profile or if authenticated
        if (!$currentUser && $currentUser['id'] != $id) {
            return response()->json([
                'message' => 'Authentication required'
            ], 401);
        }

        $user = User::findOrFail($id);
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $currentUser = JwtHelper::getUserFromToken($request);

        if (!$currentUser || $currentUser['id'] != $id) {
            return response()->json([
                'message' => 'Unauthorized to update this user'
            ], 403);
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:6',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $currentUser = JwtHelper::getUserFromToken($request);

        if (!$currentUser || $currentUser['id'] != $id) {
            return response()->json([
                'message' => 'Unauthorized to delete this user'
            ], 403);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted']);
    }

    /**
     * Get current authenticated user profile
     */
    public function profile(Request $request)
    {
        $currentUser = JwtHelper::getUserFromToken($request);

        if (!$currentUser) {
            return response()->json([
                'message' => 'Authentication required'
            ], 401);
        }

        $user = User::find($currentUser['id']);

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

    /**
     * Validate user exists (for order service)
     */
    public function validateUser(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'valid' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }
}
