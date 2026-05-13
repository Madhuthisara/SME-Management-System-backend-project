<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\DB;

class CartService
{
    /**
     * Get or create a cart for the customer.
     */
    public function getCartForCustomer(string $customerId): Cart
    {
        return Cart::firstOrCreate(['customer_id' => $customerId]);
    }

    /**
     * Get the current items in the cart.
     */
    public function getCartItems(string $customerId)
    {
        $cart = $this->getCartForCustomer($customerId);
        return $cart->items()->with('product', 'product.images')->get();
    }

    /**
     * Sync frontend cart with database
     */
    public function syncCart(string $customerId, array $localItems)
    {
        return DB::transaction(function () use ($customerId, $localItems) {
            $cart = $this->getCartForCustomer($customerId);

            // We can choose to merge or replace.
            // Let's iterate over local items and add/update them.
            // This is a simple merge strategy: if item exists (by product_id, size, color), add qty or update qty.
            // Currently, we will just sync the frontend state if frontend has items, 
            // but a true merge might sum quantities. Let's merge by adding up quantities, or replacing.
            // Frontend will usually send its entire cart.

            foreach ($localItems as $item) {
                $existingItem = $cart->items()
                    ->where('product_id', $item['productId'])
                    ->where('size', $item['size'])
                    ->where('color', $item['color'])
                    ->first();

                if ($existingItem) {
                    $existingItem->update([
                        'quantity' => max($existingItem->quantity, $item['quantity']) // Take the max or sum? Max is safer to avoid double sync.
                    ]);
                } else {
                    $cart->items()->create([
                        'product_id' => $item['productId'],
                        'quantity' => $item['quantity'],
                        'size' => $item['size'] ?? null,
                        'color' => $item['color'] ?? null,
                    ]);
                }
            }

            return $this->getCartItems($customerId);
        });
    }

    /**
     * Sync full cart completely replacing state (useful for direct sync)
     */
    public function overwriteCart(string $customerId, array $items)
    {
        return DB::transaction(function () use ($customerId, $items) {
            $cart = $this->getCartForCustomer($customerId);
            
            // Delete old items
            $cart->items()->delete();

            // Insert new items
            foreach ($items as $item) {
                $cart->items()->create([
                    'product_id' => $item['productId'],
                    'quantity' => $item['quantity'],
                    'size' => $item['size'] ?? null,
                    'color' => $item['color'] ?? null,
                ]);
            }

            return $this->getCartItems($customerId);
        });
    }

    public function addToCart(string $customerId, array $data)
    {
        $cart = $this->getCartForCustomer($customerId);

        $existingItem = $cart->items()
            ->where('product_id', $data['product_id'])
            ->where('size', $data['size'] ?? null)
            ->where('color', $data['color'] ?? null)
            ->first();

        if ($existingItem) {
            $existingItem->increment('quantity', $data['quantity'] ?? 1);
            return $existingItem->fresh();
        }

        return $cart->items()->create($data);
    }

    public function removeCartItem(string $customerId, string $itemId)
    {
        $cart = $this->getCartForCustomer($customerId);
        $cart->items()->where('id', $itemId)->delete();
    }

    public function clearCart(string $customerId)
    {
        $cart = $this->getCartForCustomer($customerId);
        $cart->items()->delete();
    }
}
