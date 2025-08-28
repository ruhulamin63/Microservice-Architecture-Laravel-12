<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Helpers\JwtHelper;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     * Only show orders for the authenticated user
     */
    public function index(Request $request)
    {
        $currentUser = JwtHelper::getUserFromToken($request);

        if (!$currentUser) {
            return response()->json([
                'message' => 'Authentication required'
            ], 401);
        }

        $orders = Order::where('user_id', $currentUser['id'])->get();

        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     * Create order for authenticated user
     */
    public function store(Request $request)
    {
        $currentUser = JwtHelper::getUserFromToken($request);

        if (!$currentUser) {
            return response()->json([
                'message' => 'Authentication required'
            ], 401);
        }

        $validated = $request->validate([
            'product' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'total' => 'required|numeric|min:0',
        ]);

        // Validate user exists in user-service
        try {
            $userResponse = Http::timeout(5)->get('http://localhost:8000/api/users/validate/' . $currentUser['id']);

            if ($userResponse->failed()) {
                return response()->json([
                    'message' => 'User validation failed',
                    'error' => 'INVALID_USER'
                ], 400);
            }

            $userData = $userResponse->json();
            if (!$userData['valid']) {
                return response()->json([
                    'message' => 'Invalid user',
                    'error' => 'INVALID_USER'
                ], 400);
            }
        } catch (\Exception $e) {
            // Fallback: assume user is valid if service is down
            \Log::warning('User service unavailable during order creation: ' . $e->getMessage());
        }

        DB::beginTransaction();

        try {
            $order = Order::create([
                'user_id' => $currentUser['id'],
                'product' => $validated['product'],
                'quantity' => $validated['quantity'],
                'total' => $validated['total'],
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Order created successfully',
                'order' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create order',
                'error' => 'ORDER_CREATION_FAILED'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * Only allow users to view their own orders
     */
    public function show(Request $request, string $id)
    {
        $currentUser = JwtHelper::getUserFromToken($request);

        if (!$currentUser) {
            return response()->json([
                'message' => 'Authentication required'
            ], 401);
        }

        $order = Order::where('id', $id)->where('user_id', $currentUser['id'])->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json($order);
    }

    /**
     * Update the specified resource in storage.
     * Only allow users to update their own orders
     */
    public function update(Request $request, string $id)
    {
        $currentUser = JwtHelper::getUserFromToken($request);

        if (!$currentUser) {
            return response()->json([
                'message' => 'Authentication required'
            ], 401);
        }

        $order = Order::where('id', $id)->where('user_id', $currentUser['id'])->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        $validated = $request->validate([
            'product' => 'sometimes|required|string|max:255',
            'quantity' => 'sometimes|required|integer|min:1',
            'total' => 'sometimes|required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $order->update($validated);
            DB::commit();

            return response()->json([
                'message' => 'Order updated successfully',
                'order' => $order
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update order',
                'error' => 'ORDER_UPDATE_FAILED'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * Only allow users to delete their own orders
     */
    public function destroy(Request $request, string $id)
    {
        $currentUser = JwtHelper::getUserFromToken($request);

        if (!$currentUser) {
            return response()->json([
                'message' => 'Authentication required'
            ], 401);
        }

        $order = Order::where('id', $id)->where('user_id', $currentUser['id'])->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $order->delete();
            DB::commit();

            return response()->json([
                'message' => 'Order deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete order',
                'error' => 'ORDER_DELETION_FAILED'
            ], 500);
        }
    }

    /**
     * Get user's order statistics
     */
    public function statistics(Request $request)
    {
        $currentUser = JwtHelper::getUserFromToken($request);

        if (!$currentUser) {
            return response()->json([
                'message' => 'Authentication required'
            ], 401);
        }

        $stats = [
            'total_orders' => Order::where('user_id', $currentUser['id'])->count(),
            'total_spent' => Order::where('user_id', $currentUser['id'])->sum('total'),
            'recent_orders' => Order::where('user_id', $currentUser['id'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
        ];

        return response()->json($stats);
    }
}
