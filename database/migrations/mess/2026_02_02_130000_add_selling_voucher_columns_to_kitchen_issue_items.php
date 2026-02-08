<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add Selling Voucher columns to kitchen_issue_items.
     */
    public function up()
    {
        Schema::table('kitchen_issue_items', function (Blueprint $table) {
            if (!Schema::hasColumn('kitchen_issue_items', 'item_subcategory_id')) {
                $table->unsignedBigInteger('item_subcategory_id')->nullable()->after('kitchen_issue_master_pk')->comment('FK mess_item_subcategories');
            }
            if (!Schema::hasColumn('kitchen_issue_items', 'available_quantity')) {
                $table->decimal('available_quantity', 10, 2)->default(0)->after('quantity')->comment('Available qty at issue time');
            }
            if (!Schema::hasColumn('kitchen_issue_items', 'return_quantity')) {
                $table->decimal('return_quantity', 10, 2)->default(0)->after('available_quantity')->comment('Returned qty');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('kitchen_issue_items', function (Blueprint $table) {
            if (Schema::hasColumn('kitchen_issue_items', 'item_subcategory_id')) {
                $table->dropColumn('item_subcategory_id');
            }
            if (Schema::hasColumn('kitchen_issue_items', 'available_quantity')) {
                $table->dropColumn('available_quantity');
            }
            if (Schema::hasColumn('kitchen_issue_items', 'return_quantity')) {
                $table->dropColumn('return_quantity');
            }
        });
    }
};
