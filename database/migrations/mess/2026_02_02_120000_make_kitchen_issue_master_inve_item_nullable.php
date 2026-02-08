<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Selling Voucher: one master per voucher with multiple items in kitchen_issue_items;
     * master does not store a single item, so inve_item_master_pk must be nullable.
     */
    public function up()
    {
        DB::statement('ALTER TABLE kitchen_issue_master MODIFY inve_item_master_pk BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement('ALTER TABLE kitchen_issue_master MODIFY inve_item_master_pk BIGINT UNSIGNED NOT NULL');
    }
};
