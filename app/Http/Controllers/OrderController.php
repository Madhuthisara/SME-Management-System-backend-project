<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $businessId = $request->query('business_id');

        if (!$businessId) {
            return $this->errorResponse('Business ID is required', 422);
        }

        try {
            $orders = $this->orderService->getAllOrders($businessId);
            return $this->successResponse($orders, 'Orders retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function show(Request $request): JsonResponse
    {
        $id = $request->query('id');

        if (!$id) {
            return $this->errorResponse('Order ID is required', 422);
        }

        try {
            $order = $this->orderService->getOrderById($id);
            if (!$order) {
                return $this->errorResponse('Order not found', 404);
            }
            return $this->successResponse($order, 'Order retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'business_id' => 'required|exists:businesses,id',
                'customer_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:20',
                'secondary_phone_number' => 'nullable|string|max:20',
                'delivery_address' => 'required|string',
                'district' => 'required|string|max:255',
                'nearest_main_city' => 'required|string|max:255',
                'source' => 'required|in:whatsapp,messenger,tiktok,instagram,manual,other',
                'payment_method' => 'required|string|max:50',
                'custom_status_id' => 'nullable|exists:order_statuses,id',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.attribute_option_ids' => 'nullable|array',
                'items.*.attribute_option_ids.*' => 'exists:attribute_options,option_id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
            ]);

            $order = $this->orderService->createOrder($validatedData);
            return $this->successResponse($order, 'Order created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $id = $request->query('id');
        if (!$id) {
            return $this->errorResponse('Order ID is required', 422);
        }

        $order = Order::find($id);
        if (!$order) {
            return $this->errorResponse('Order not found', 404);
        }

        try {
            $validatedData = $request->validate([
                'status' => 'nullable|in:new,processing,delivered,rejected,returned,exchanged',
                'custom_status_id' => 'nullable|exists:order_statuses,id',
                'notes' => 'nullable|string',
            ]);

            $updatedOrder = $this->orderService->updateOrderStatus(
                $order,
                $validatedData['status'] ?? null,
                $validatedData['notes'] ?? null,
                $validatedData['custom_status_id'] ?? null
            );
            return $this->successResponse($updatedOrder, 'Order status updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        $id = $request->query('id');

        if (!$id) {
            return $this->errorResponse('Order ID is required', 422);
        }

        $order = Order::find($id);

        if (!$order) {
            return $this->errorResponse('Order not found', 404);
        }

        try {
            $this->orderService->deleteOrder($order);
            return $this->successResponse([], 'Order deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete order', 500);
        }
    }

    public function customerStore(Request $request): JsonResponse
    {
        try {
            $customer = auth('customer')->user();

            $validatedData = $request->validate([
                'business_id' => 'required|exists:businesses,id',
                'customer_name' => 'required|string|max:255',
                'phone_number' => 'required|string|max:20',
                'secondary_phone_number' => 'nullable|string|max:20',
                'delivery_address' => 'required|string',
                'district' => 'required|string|max:255',
                'nearest_main_city' => 'required|string|max:255',
                'main_city' => 'required|string|max:255',
                'postal_code' => 'nullable|string|max:20',
                'payment_method' => 'required|string|max:50',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.attribute_option_ids' => 'nullable|array',
                'items.*.attribute_option_ids.*' => 'exists:attribute_options,option_id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
            ]);

            $validatedData['customer_id'] = $customer ? $customer->id : null;
            $validatedData['source'] = 'other'; // default for e-store orders

            $order = $this->orderService->createOrder($validatedData);
            return $this->successResponse($order, 'Order placed successfully!', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function customerOrders(Request $request): JsonResponse
    {
        try {
            $customer = auth('customer')->user();

            if (!$customer) {
                return $this->errorResponse('Unauthenticated customer', 401);
            }

            $orders = Order::with('items.product')
                ->where('customer_id', $customer->id)
                ->latest()
                ->get();
            
            return $this->successResponse($orders, 'Orders retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
