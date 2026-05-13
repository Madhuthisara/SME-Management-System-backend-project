<?php

namespace App\Http\Controllers;

use App\Models\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderStatusController extends Controller
{
    /**
     * Get all order statuses for a business.
     */
    public function index(Request $request): JsonResponse
    {
        $businessId = $request->query('business_id');
        $statuses = OrderStatus::where('business_id', $businessId)
            ->orderBy('name')
            ->get();

        return $this->successResponse($statuses, 'Order statuses retrieved successfully');
    }

    /**
     * Create a new order status.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'business_id' => 'required|exists:businesses,id',
                'name'        => 'required|string|max:100',
                'color'       => 'nullable|string|max:20',
            ]);

            // Check uniqueness per business
            $exists = OrderStatus::where('business_id', $validated['business_id'])
                ->where('name', $validated['name'])
                ->exists();

            if ($exists) {
                return $this->errorResponse('A status with this name already exists for your business.', 422);
            }

            $status = OrderStatus::create($validated);
            return $this->successResponse($status, 'Order status created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create order status', 500, [], $e);
        }
    }

    /**
     * Update an existing order status.
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id'    => 'required|exists:order_statuses,id',
                'name'  => 'sometimes|required|string|max:100',
                'color' => 'nullable|string|max:20',
            ]);

            $status = OrderStatus::find($validated['id']);

            if (!$status) {
                return $this->errorResponse('Order status not found', 404);
            }

            // Check uniqueness if name is being updated
            if (isset($validated['name'])) {
                $exists = OrderStatus::where('business_id', $status->business_id)
                    ->where('name', $validated['name'])
                    ->where('id', '!=', $status->id)
                    ->exists();

                if ($exists) {
                    return $this->errorResponse('A status with this name already exists for your business.', 422);
                }
            }

            $status->update($validated);
            return $this->successResponse($status, 'Order status updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update order status', 500, [], $e);
        }
    }

    /**
     * Delete an order status.
     */
    public function destroy(Request $request): JsonResponse
    {
        $id = $request->query('id');

        if (!$id) {
            return $this->errorResponse('Order status ID is required', 422);
        }

        $status = OrderStatus::find($id);

        if (!$status) {
            return $this->errorResponse('Order status not found', 404);
        }

        // Block deletion if any order is using this status
        if ($status->orders()->exists()) {
            return $this->errorResponse('This status is assigned to one or more orders and cannot be deleted.', 422);
        }

        try {
            $status->delete();
            return $this->successResponse([], 'Order status deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete order status', 500, [], $e);
        }
    }
}
