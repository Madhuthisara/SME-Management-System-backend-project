<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\MaterialStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $businessId = $request->user()->business_id;

        // Basic Stats
        $totalOrders = Order::where('business_id', $businessId)->count();
        $totalRevenue = Order::where('business_id', $businessId)
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->sum('total_amount');
        $totalProducts = Product::where('business_id', $businessId)->count();
        
        $lowStockMaterials = MaterialStock::where('business_id', $businessId)
            ->where(function($query) {
                $query->whereColumn('quantity', '<=', 'reorder_level')
                      ->orWhere('quantity', '<', 5);
            })
            ->count();

        // Recent Orders
        $recentOrders = Order::where('business_id', $businessId)
            ->latest()
            ->take(5)
            ->get(['id', 'customer_name', 'total_amount', 'status', 'created_at']);

        // Revenue Chart Data (Last 30 days)
        $revenueData = Order::where('business_id', $businessId)
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_orders' => (int)$totalOrders,
                    'total_revenue' => (float)$totalRevenue,
                    'total_products' => (int)$totalProducts,
                    'low_stock_materials' => (int)$lowStockMaterials,
                ],
                'recent_orders' => $recentOrders,
                'chart_data' => $revenueData
            ]
        ]);
    }
}
