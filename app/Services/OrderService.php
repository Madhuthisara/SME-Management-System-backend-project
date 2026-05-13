<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class OrderService
{
    public function getAllOrders(string $businessId): Collection
    {
        return Order::with(['customStatus'])
            ->where('business_id', $businessId)
            ->latest()
            ->get();
    }

    public function getOrderById(string $orderId): ?Order
    {
        return Order::with(['items.product', 'customStatus'])->find($orderId);
    }

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $totalAmount = 0;
            foreach ($data['items'] as $item) {
                $totalAmount += $item['quantity'] * $item['unit_price'];
            }

            $order = Order::create([
                'business_id' => $data['business_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'customer_name' => $data['customer_name'],
                'phone_number' => $data['phone_number'],
                'secondary_phone_number' => $data['secondary_phone_number'] ?? null,
                'delivery_address' => $data['delivery_address'],
                'district' => $data['district'],
                'nearest_main_city' => $data['nearest_main_city'],
                'main_city' => $data['main_city'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'source' => $data['source'],
                'payment_method' => $data['payment_method'],
                'status' => 'new',
                'custom_status_id' => $data['custom_status_id'] ?? null,
                'total_amount' => $totalAmount,
                'notes' => $data['notes'] ?? null,
            ]);

            $totalAmount = 0;
            foreach ($data['items'] as $item) {
                $quantityToDeduct = $item['quantity'];
                $attributeOptionIds = $item['attribute_option_ids'] ?? [];

                // Store selected attribute options for reference (Common Solution for POD)
                $selectedAttributes = [];
                if (!empty($attributeOptionIds)) {
                    $options = \App\Models\AttributeOption::with('attribute')
                        ->whereIn('option_id', $attributeOptionIds)
                        ->get();
                    foreach ($options as $opt) {
                        $selectedAttributes[] = [
                            'attribute_name' => $opt->attribute->name,
                            'option_name' => $opt->name,
                            'option_id' => $opt->option_id
                        ];
                    }
                }

                // Find matching ProductStock entries (FIFO - oldest first)
                $stockQuery = \App\Models\ProductStock::where('product_id', $item['product_id'])
                    ->where('business_id', $data['business_id'])
                    ->where('quantity', '>', 0);

                if (!empty($attributeOptionIds)) {
                    // Match exact attributes if provided
                    $stockQuery->whereHas('attributeOptions', function ($q) use ($attributeOptionIds) {
                        $q->whereIn('attribute_options.option_id', $attributeOptionIds);
                    }, '=', count($attributeOptionIds))
                    ->whereDoesntHave('attributeOptions', function ($q) use ($attributeOptionIds) {
                        $q->whereNotIn('attribute_options.option_id', $attributeOptionIds);
                    });
                } else {
                    // If no attributes provided, we don't apply strict filters here.
                    // This is handled by frontend ensuring they select required attributes.
                }

                $matchingStocks = $stockQuery->orderBy('created_at', 'asc')->get();
                $stockFound = $matchingStocks->sum('quantity');

                // Even if stock is insufficient, we proceed with creation (Print on Demand model)
                // We deduct what we can, and the rest is recorded as null stock_id
                
                $remainingQuantity = $quantityToDeduct;
                foreach ($matchingStocks as $stock) {
                    if ($remainingQuantity <= 0) break;

                    $deductNow = min($stock->quantity, $remainingQuantity);
                    $stock->decrement('quantity', $deductNow);

                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'product_stock_id' => $stock->id,
                        'quantity' => $deductNow,
                        'unit_price' => $item['unit_price'],
                        'total_price' => $deductNow * $item['unit_price'],
                        'selected_attributes' => $selectedAttributes
                    ]);

                    $remainingQuantity -= $deductNow;
                }

                // If no stock was found or quantity remaining, create a "Pending Production" item
                if ($remainingQuantity > 0) {
                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'product_stock_id' => null,
                        'quantity' => $remainingQuantity,
                        'unit_price' => $item['unit_price'],
                        'total_price' => $remainingQuantity * $item['unit_price'],
                        'selected_attributes' => $selectedAttributes
                    ]);
                }

                $totalAmount += $quantityToDeduct * $item['unit_price'];
            }

            $order->update(['total_amount' => $totalAmount]);

            return $order->load(['items.product', 'customStatus']);
        });
    }

    public function updateOrderStatus(Order $order, ?string $status, ?string $notes = null, ?string $customStatusId = null): Order
    {
        $updateData = ['notes' => $notes ?? $order->notes];
        if ($status !== null) {
            $updateData['status'] = $status;
        }
        if ($customStatusId !== null) {
            $updateData['custom_status_id'] = $customStatusId;
        }
        $order->update($updateData);
        return $order->load('customStatus');
    }

    public function deleteOrder(Order $order): bool
    {
        return $order->delete();
    }
}
