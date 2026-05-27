<?php
// app/Services/ReportService.php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getSalesRevenueData(array $filters = [])
    {
        $startDate = (!empty($filters['start_date']) && $filters['start_date'] !== '0') ? $filters['start_date'] : now()->subDays(30)->toDateString();
        $endDate = (!empty($filters['end_date']) && $filters['end_date'] !== '0') ? $filters['end_date'] : now()->toDateString();

        $query = Order::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(id) as total_orders'),
                    DB::raw('SUM(total_amount) as total_revenue')
                )
                ->whereBetween(DB::raw('DATE(created_at)'), [$startDate, $endDate]);

        if (!empty($filters['status']) && $filters['status'] !== '0') {
            $query->where('status', $filters['status']);
        } else if (empty($filters['status']) || $filters['status'] === '0') {
            $query->whereNotIn('status', ['cancelled', 'failed']);
        }

        if (!empty($filters['district']) && $filters['district'] !== '0') {
            $query->where('district', $filters['district']);
        }

        if ((!empty($filters['product_name']) && $filters['product_name'] !== '0') || 
            (!empty($filters['size']) && $filters['size'] !== '0') || 
            (!empty($filters['color']) && $filters['color'] !== '0')) {
            $query->whereHas('items', function ($q) use ($filters) {
                if (!empty($filters['product_name']) && $filters['product_name'] !== '0') {
                    $q->whereHas('product', function ($q2) use ($filters) {
                        $q2->where('name', 'like', '%' . $filters['product_name'] . '%');
                    });
                }
                if (!empty($filters['size']) && $filters['size'] !== '0') {
                    $q->whereJsonContains('selected_attributes->Size', $filters['size']);
                }
                if (!empty($filters['color']) && $filters['color'] !== '0') {
                    $q->whereJsonContains('selected_attributes->Color', $filters['color']);
                }
            });
        }

        return $query->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date', 'asc')
                ->get();
    }

    public function getTopSellingProductsData(int $limit = 10, array $filters = [])
    {
        $query = \App\Models\OrderItem::select(
                    'product_id',
                    'products.name as product_name',
                    'products.sku as product_sku',
                    DB::raw('SUM(order_items.quantity) as total_quantity_sold'),
                    DB::raw('SUM(order_items.total_price) as total_revenue_generated')
                )
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id');

        if (!empty($filters['status']) && $filters['status'] !== '0') {
            $query->where('orders.status', $filters['status']);
        } else if (empty($filters['status']) || $filters['status'] === '0') {
            $query->whereNotIn('orders.status', ['cancelled', 'failed']);
        }

        if (!empty($filters['start_date']) && $filters['start_date'] !== '0' && !empty($filters['end_date']) && $filters['end_date'] !== '0') {
            $query->whereBetween(DB::raw('DATE(orders.created_at)'), [$filters['start_date'], $filters['end_date']]);
        }

        if (!empty($filters['district']) && $filters['district'] !== '0') {
            $query->where('orders.district', $filters['district']);
        }

        if (!empty($filters['product_name']) && $filters['product_name'] !== '0') {
            $query->where('products.name', 'like', '%' . $filters['product_name'] . '%');
        }

        if (!empty($filters['size']) && $filters['size'] !== '0') {
            $query->whereJsonContains('order_items.selected_attributes->Size', $filters['size']);
        }

        if (!empty($filters['color']) && $filters['color'] !== '0') {
            $query->whereJsonContains('order_items.selected_attributes->Color', $filters['color']);
        }

        return $query->groupBy('product_id', 'products.name', 'products.sku')
                ->orderBy('total_quantity_sold', 'desc')
                ->limit($limit)
                ->get();
    }
}