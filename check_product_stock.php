<?php
use App\Models\ProductStock;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$productId = '01kkb343qap0hxc4ca7cd0s9k7';
$stocks = ProductStock::where('product_id', $productId)->with('attributeOptions')->get();

if ($stocks->isEmpty()) {
    echo "No stock records found for product $productId\n";
} else {
    foreach ($stocks as $s) {
        $optionIds = $s->attributeOptions->pluck('option_id')->implode(', ');
        echo "Stock ID: {$s->id}, Qty: {$s->quantity}, Attributes: [{$optionIds}]\n";
    }
    echo "Total Qty: " . $stocks->sum('quantity') . "\n";
}
