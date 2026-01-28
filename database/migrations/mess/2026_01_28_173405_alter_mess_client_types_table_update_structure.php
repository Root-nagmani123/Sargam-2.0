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
        // Rename type_name to client_name if it exists
        if (Schema::hasColumn('mess_client_types', 'type_name') && !Schema::hasColumn('mess_client_types', 'client_name')) {
            \DB::statement('ALTER TABLE `mess_client_types` CHANGE `type_name` `client_name` VARCHAR(255)');
        }
        
        // Add client_type column if it doesn't exist
        if (!Schema::hasColumn('mess_client_types', 'client_type')) {
            Schema::table('mess_client_types', function (Blueprint $table) {
                $table->string('client_type')->default('employee')->after('id');
            });
            
            // Set default client_type for existing records
            \DB::table('mess_client_types')
                ->whereNull('client_type')
                ->update(['client_type' => 'employee']);
        }
        
        // Convert is_active to status
        if (Schema::hasColumn('mess_client_types', 'is_active') && !Schema::hasColumn('mess_client_types', 'status')) {
            // Add status column first
            Schema::table('mess_client_types', function (Blueprint $table) {
                $table->string('status')->default('active')->after('client_name');
            });
            
            // Migrate data from is_active to status
            \DB::table('mess_client_types')
                ->where('is_active', true)
                ->update(['status' => 'active']);
            
            \DB::table('mess_client_types')
                ->where('is_active', false)
                ->update(['status' => 'inactive']);
            
            // Drop is_active column
            Schema::table('mess_client_types', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        } elseif (!Schema::hasColumn('mess_client_types', 'status')) {
            // Add status column if is_active doesn't exist
            Schema::table('mess_client_types', function (Blueprint $table) {
                $table->string('status')->default('active')->after('client_name');
            });
            
            // Set default status for existing records
            \DB::table('mess_client_types')
                ->whereNull('status')
                ->update(['status' => 'active']);
        }
        
        // Drop columns that are no longer needed
        Schema::table('mess_client_types', function (Blueprint $table) {
            if (Schema::hasColumn('mess_client_types', 'type_code')) {
                $table->dropColumn('type_code');
            }
            if (Schema::hasColumn('mess_client_types', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('mess_client_types', 'default_credit_limit')) {
                $table->dropColumn('default_credit_limit');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert client_name back to type_name
        if (Schema::hasColumn('mess_client_types', 'client_name') && !Schema::hasColumn('mess_client_types', 'type_name')) {
            \DB::statement('ALTER TABLE `mess_client_types` CHANGE `client_name` `type_name` VARCHAR(255)');
        }
        
        // Add back dropped columns
        Schema::table('mess_client_types', function (Blueprint $table) {
            if (!Schema::hasColumn('mess_client_types', 'type_code')) {
                $table->string('type_code')->unique()->after('type_name');
            }
            if (!Schema::hasColumn('mess_client_types', 'description')) {
                $table->text('description')->nullable()->after('type_code');
            }
            if (!Schema::hasColumn('mess_client_types', 'default_credit_limit')) {
                $table->decimal('default_credit_limit', 10, 2)->nullable()->after('description');
            }
        });
        
        // Revert status back to is_active
        if (Schema::hasColumn('mess_client_types', 'status')) {
            // Add is_active column first
            Schema::table('mess_client_types', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('type_name');
            });
            
            // Migrate data from status to is_active
            \DB::table('mess_client_types')
                ->where('status', 'active')
                ->update(['is_active' => true]);
            
            \DB::table('mess_client_types')
                ->where('status', 'inactive')
                ->update(['is_active' => false]);
            
            // Drop status column
            Schema::table('mess_client_types', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
        
        // Remove client_type column
        if (Schema::hasColumn('mess_client_types', 'client_type')) {
            Schema::table('mess_client_types', function (Blueprint $table) {
                $table->dropColumn('client_type');
            });
        }
    }
};
