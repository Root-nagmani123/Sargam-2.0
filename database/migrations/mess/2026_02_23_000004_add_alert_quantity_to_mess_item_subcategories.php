<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds alert_quantity to mess_item_subcategories for low-stock alerts.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('mess_item_subcategories') && !Schema::hasColumn('mess_item_subcategories', 'alert_quantity')) {
            Schema::table('mess_item_subcategories', function (Blueprint $table) {
                $table->decimal('alert_quantity', 12, 4)->nullable()->comment('Minimum quantity; alert when remaining_quantity <= this');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('mess_item_subcategories', 'alert_quantity')) {
            Schema::table('mess_item_subcategories', function (Blueprint $table) {
                $table->dropColumn('alert_quantity');
            });
        }
    }
};
