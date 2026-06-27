<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['member_id', 'status'], 'orders_member_status_index');
            $table->index('created_at', 'orders_created_at_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['order_id', 'status'], 'payments_order_status_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('name', 'products_name_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_member_status_index');
            $table->dropIndex('orders_created_at_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_order_status_index');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_name_index');
        });
    }
};
