<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = [
    'order_items',
    'orders',
    'product_stock_attribute_options',
    'product_stock_materials',
    'product_stocks',
    'product_images',
    'products',
    'product_template_materials',
    'product_templates',
    'material_stock_attribute_options',
    'material_stocks',
    'material_attribute',
    'materials',
    'attribute_options',
    'attributes',
    'categories',
];

Schema::disableForeignKeyConstraints();

foreach ($tables as $table) {
    if (Schema::hasTable($table)) {
        DB::table($table)->truncate();
        echo "Truncated: $table\n";
    }
}

Schema::enableForeignKeyConstraints();

echo "Data reset complete. User and Business accounts preserved.\n";
