<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure mess_store_allocations has sub_store_id and allocation_date for new flow
        if (Schema::hasTable('mess_store_allocations')) {
            if (!Schema::hasColumn('mess_store_allocations', 'sub_store_id')) {
                Schema::table('mess_store_allocations', function (Blueprint $table) {
                    $table->unsignedBigInteger('sub_store_id')->nullable()->after('id');
                    $table->foreign('sub_store_id')->references('id')->on('mess_sub_stores')->onDelete('cascade');
                });
            }
            if (!Schema::hasColumn('mess_store_allocations', 'allocation_date')) {
                Schema::table('mess_store_allocations', function (Blueprint $table) {
                    $table->date('allocation_date')->nullable()->after('sub_store_id');
                });
            }
        }

        Schema::create('mess_store_allocation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_allocation_id');
            $table->unsignedBigInteger('item_subcategory_id');
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('unit', 50)->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('store_allocation_id')->references('id')->on('mess_store_allocations')->onDelete('cascade');
            $table->foreign('item_subcategory_id')->references('id')->on('mess_item_subcategories')->onDelete('restrict');
            $table->index('store_allocation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mess_store_allocation_items');
        if (Schema::hasTable('mess_store_allocations')) {
            if (Schema::hasColumn('mess_store_allocations', 'sub_store_id')) {
                Schema::table('mess_store_allocations', function (Blueprint $table) {
                    $table->dropForeign(['sub_store_id']);
                    $table->dropColumn('sub_store_id');
                });
            }
            if (Schema::hasColumn('mess_store_allocations', 'allocation_date') && !Schema::hasColumn('mess_store_allocations', 'store_name')) {
                Schema::table('mess_store_allocations', function (Blueprint $table) {
                    $table->dropColumn('allocation_date');
                });
            }
        }
    }
};
