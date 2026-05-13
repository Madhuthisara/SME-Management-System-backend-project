<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    public function index(Request $request): JsonResponse
    {
        // customer authentication guards attach the customer to the user
        $customerId = auth('customer')->user()->id;
        $items = $this->cartService->getCartItems($customerId);

        return $this->successResponse($this->formatItems($items), 'Cart retrieved successfully');
    }

    public function sync(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'items' => 'array',
            'items.*.productId' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.size' => 'nullable|string',
            'items.*.color' => 'nullable|string',
        ]);

        $customerId = auth('customer')->user()->id;
        // Overwrite the cart with frontend state to maintain absolute sync 
        $items = $this->cartService->overwriteCart($customerId, $validatedData['items'] ?? []);

        return $this->successResponse($this->formatItems($items), 'Cart synced successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'product_id' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'size' => 'nullable|string',
            'color' => 'nullable|string',
        ]);

        $customerId = auth('customer')->user()->id;
        $item = $this->cartService->addToCart($customerId, $validatedData);

        return $this->successResponse($item, 'Item added to cart successfully');
    }

    public function destroy(Request $request, string $itemId): JsonResponse
    {
        $customerId = auth('customer')->user()->id;
        $this->cartService->removeCartItem($customerId, $itemId);

        return $this->successResponse(null, 'Item removed from cart');
    }

    public function clear(Request $request): JsonResponse
    {
        $customerId = auth('customer')->user()->id;
        $this->cartService->clearCart($customerId);

        return $this->successResponse(null, 'Cart cleared');
    }

    private function formatItems($items)
    {
        return $items->map(function ($item) {
            return [
                // frontend relies on this ID format for cart uniqueness
                'id' => "{$item->product_id}-" . ($item->size ?? 'default') . "-" . ($item->color ?? 'default'), 
                'productId' => $item->product_id,
                'name' => $item->product->name ?? 'Unknown',
                'price' => (float) ($item->product->base_price ?? 0),
                'image' => $item->product->thumbnail_url ?? ($item->product->images->first()->image_url ?? ''),
                'quantity' => $item->quantity,
                'size' => $item->size,
                'color' => $item->color,
                'db_id' => $item->id, // Provide db id just in case
            ];
        });
    }
}
