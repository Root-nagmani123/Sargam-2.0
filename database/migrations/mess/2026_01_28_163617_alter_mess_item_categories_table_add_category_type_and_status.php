<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add category_type column if it doesn't exist
        if (!Schema::hasColumn('mess_item_categories', 'category_type')) {
            // Determine the column to place after (prefer category_name, fallback to name)
            $afterColumn = 'category_name';
            if (!Schema::hasColumn('mess_item_categories', 'category_name') && Schema::hasColumn('mess_item_categories', 'name')) {
                $afterColumn = 'name';
            }
            
            Schema::table('mess_item_categories', function (Blueprint $table) use ($afterColumn) {
                $table->string('category_type')->default('raw_material')->after($afterColumn);
            });
            
            // Set default value for existing records
            \DB::table('mess_item_categories')
                ->whereNull('category_type')
                ->update(['category_type' => 'raw_material']);
        }
        
        // Add status column if it doesn't exist
        if (!Schema::hasColumn('mess_item_categories', 'status')) {
            Schema::table('mess_item_categories', function (Blueprint $table) {
                $table->string('status')->default('active')->after('description');
            });
            
            // Set default status for existing records
            \DB::table('mess_item_categories')
                ->whereNull('status')
                ->update(['status' => 'active']);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mess_item_categories', function (Blueprint $table) {
            // Remove category_type
            if (Schema::hasColumn('mess_item_categories', 'category_type')) {
                $table->dropColumn('category_type');
            }
        });
        
        // Remove status column
        if (Schema::hasColumn('mess_item_categories', 'status')) {
            Schema::table('mess_item_categories', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
        
    }
};
