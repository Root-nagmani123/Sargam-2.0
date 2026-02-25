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
        // Step 1: Rename columns first (outside Schema::table to avoid issues)
        if (Schema::hasColumn('mess_item_subcategories', 'subcategory_name') && !Schema::hasColumn('mess_item_subcategories', 'item_name')) {
            \DB::statement('ALTER TABLE `mess_item_subcategories` CHANGE `subcategory_name` `item_name` VARCHAR(255)');
        }
        
        if (Schema::hasColumn('mess_item_subcategories', 'subcategory_code') && !Schema::hasColumn('mess_item_subcategories', 'item_code')) {
            \DB::statement('ALTER TABLE `mess_item_subcategories` CHANGE `subcategory_code` `item_code` VARCHAR(255)');
        }
        
        // Step 2: Determine which code column exists now (check after rename attempt)
        // Use item_code if it exists, otherwise use description as safe fallback
        $codeColumn = Schema::hasColumn('mess_item_subcategories', 'item_code') ? 'item_code' : 'description';
        
        // Step 3: Add new columns after the determined column
        if (!Schema::hasColumn('mess_item_subcategories', 'unit_measurement')) {
            \DB::statement("ALTER TABLE `mess_item_subcategories` ADD COLUMN `unit_measurement` VARCHAR(255) NULL AFTER `{$codeColumn}`");
        }
        
        if (!Schema::hasColumn('mess_item_subcategories', 'standard_cost')) {
            if (Schema::hasColumn('mess_item_subcategories', 'unit_measurement')) {
                \DB::statement("ALTER TABLE `mess_item_subcategories` ADD COLUMN `standard_cost` DECIMAL(10, 2) NULL AFTER `unit_measurement`");
            } else {
                // Fallback if unit_measurement wasn't added
                \DB::statement("ALTER TABLE `mess_item_subcategories` ADD COLUMN `standard_cost` DECIMAL(10, 2) NULL AFTER `{$codeColumn}`");
            }
        }
        
        // Convert is_active to status
        if (Schema::hasColumn('mess_item_subcategories', 'is_active') && !Schema::hasColumn('mess_item_subcategories', 'status')) {
            // Add status column first
            Schema::table('mess_item_subcategories', function (Blueprint $table) {
                $table->string('status')->default('active')->after('description');
            });
            
            // Migrate data from is_active to status
            \DB::table('mess_item_subcategories')
                ->where('is_active', true)
                ->update(['status' => 'active']);
            
            \DB::table('mess_item_subcategories')
                ->where('is_active', false)
                ->update(['status' => 'inactive']);
            
            // Drop is_active column
            Schema::table('mess_item_subcategories', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        } elseif (!Schema::hasColumn('mess_item_subcategories', 'status')) {
            // Add status column if is_active doesn't exist
            Schema::table('mess_item_subcategories', function (Blueprint $table) {
                $table->string('status')->default('active')->after('description');
            });
            
            // Set default status for existing records
            \DB::table('mess_item_subcategories')
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
        Schema::table('mess_item_subcategories', function (Blueprint $table) {
            // Remove unit_measurement
            if (Schema::hasColumn('mess_item_subcategories', 'unit_measurement')) {
                $table->dropColumn('unit_measurement');
            }
            
            // Remove standard_cost
            if (Schema::hasColumn('mess_item_subcategories', 'standard_cost')) {
                $table->dropColumn('standard_cost');
            }
        });
        
        // Revert status back to is_active
        if (Schema::hasColumn('mess_item_subcategories', 'status')) {
            // Add is_active column first
            Schema::table('mess_item_subcategories', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('description');
            });
            
            // Migrate data from status to is_active
            \DB::table('mess_item_subcategories')
                ->where('status', 'active')
                ->update(['is_active' => true]);
            
            \DB::table('mess_item_subcategories')
                ->where('status', 'inactive')
                ->update(['is_active' => false]);
            
            // Drop status column
            Schema::table('mess_item_subcategories', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
        
        // Revert item_name back to subcategory_name
        if (Schema::hasColumn('mess_item_subcategories', 'item_name') && !Schema::hasColumn('mess_item_subcategories', 'subcategory_name')) {
            \DB::statement('ALTER TABLE `mess_item_subcategories` CHANGE `item_name` `subcategory_name` VARCHAR(255)');
        }
        
        // Revert item_code back to subcategory_code
        if (Schema::hasColumn('mess_item_subcategories', 'item_code') && !Schema::hasColumn('mess_item_subcategories', 'subcategory_code')) {
            \DB::statement('ALTER TABLE `mess_item_subcategories` CHANGE `item_code` `subcategory_code` VARCHAR(255)');
        }
    }
};
