<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Selling Voucher: one master per voucher (multi-item), transfer_to_client, items with return qty.
     */
    public function up()
    {
        if (Schema::hasColumn('kitchen_issue_master', 'inve_item_master_pk')) {
            \DB::statement('ALTER TABLE kitchen_issue_master MODIFY inve_item_master_pk BIGINT UNSIGNED NULL');
        }
        if (!Schema::hasColumn('kitchen_issue_master', 'transfer_to_client')) {
            Schema::table('kitchen_issue_master', function (Blueprint $table) {
                $table->string('transfer_to_client')->nullable()->after('transfer_to')->comment('Transfer To Client name');
            });
        }

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
        if (Schema::hasColumn('kitchen_issue_master', 'inve_item_master_pk')) {
            \DB::statement('ALTER TABLE kitchen_issue_master MODIFY inve_item_master_pk BIGINT UNSIGNED NOT NULL');
        }
        if (Schema::hasColumn('kitchen_issue_master', 'transfer_to_client')) {
            Schema::table('kitchen_issue_master', function (Blueprint $table) {
                $table->dropColumn('transfer_to_client');
            });
        }

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
