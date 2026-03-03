<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // First, drop columns that have indexes
        Schema::table('kitchen_issue_master', function (Blueprint $table) {
            $columnsWithIndexes = [
                'inve_item_master_pk',
                'employee_student_pk',
                'client_type_pk',
                'status',
                'payment_type',
                'approve_status',
                'request_date',
            ];

            foreach ($columnsWithIndexes as $column) {
                if (Schema::hasColumn('kitchen_issue_master', $column)) {
                    // Laravel will automatically drop associated indexes when dropping column
                    $table->dropColumn($column);
                }
            }
        });

        // Now drop remaining columns without indexes
        Schema::table('kitchen_issue_master', function (Blueprint $table) {
            $columnsToCheck = [
                'requested_store_id',
                'quantity',
                'user_id',
                'store_employee_master_pk',
                'unit_price',
                'transfer_to',
                'bill_no',
                'send_for_approval',
                'notify_status',
                'paid_unpaid',
                'created_by',
                'modified_by',
            ];

            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('kitchen_issue_master', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Rename store column first using raw SQL (MariaDB compatible)
        if (Schema::hasColumn('kitchen_issue_master', 'inve_store_master_pk') && 
            !Schema::hasColumn('kitchen_issue_master', 'store_id')) {
            DB::statement('ALTER TABLE kitchen_issue_master CHANGE `inve_store_master_pk` `store_id` BIGINT UNSIGNED NOT NULL');
        }

        // Now add/modify columns
        Schema::table('kitchen_issue_master', function (Blueprint $table) {
            // Add client_type back with new definition
            if (!Schema::hasColumn('kitchen_issue_master', 'client_type')) {
                $table->tinyInteger('client_type')->default(1)->comment('1=Employee, 2=OT, 3=Course, 4=Other')->after('pk');
            }

            // Add payment_type back with new definition
            if (!Schema::hasColumn('kitchen_issue_master', 'payment_type')) {
                $table->tinyInteger('payment_type')->default(0)->comment('0=Cash, 1=Credit, 2=Online')->after('client_type');
            }

            // Add status back
            if (!Schema::hasColumn('kitchen_issue_master', 'status')) {
                $table->tinyInteger('status')->default(0)->comment('0=Pending, 1=Processing, 2=Approved, 3=Rejected, 4=Completed')->after('remarks');
            }

            // Add client_type_pk back (for compatibility)
            if (!Schema::hasColumn('kitchen_issue_master', 'client_type_pk')) {
                $table->unsignedBigInteger('client_type_pk')->nullable()->comment('FK to mess_client_types')->after('client_type');
            }

            // Add new columns if they don't exist
            if (!Schema::hasColumn('kitchen_issue_master', 'client_id')) {
                $table->unsignedBigInteger('client_id')->nullable()->after('payment_type')->comment('ID based on selected client type');
            }

            if (!Schema::hasColumn('kitchen_issue_master', 'name_id')) {
                $table->unsignedBigInteger('name_id')->nullable()->after('client_id')->comment('Name ID based on selected client type');
            }

            if (!Schema::hasColumn('kitchen_issue_master', 'kitchen_issue_type')) {
                $table->tinyInteger('kitchen_issue_type')->default(1)->after('store_id')->comment('1=Selling Voucher, 2=Selling Voucher with Date Range');
            }
        });

        // Add indexes in a final separate statement
        Schema::table('kitchen_issue_master', function (Blueprint $table) {
            // Try to add indexes, skip if they already exist
            $indexColumns = ['store_id', 'client_type', 'client_id', 'name_id', 'kitchen_issue_type', 'status', 'payment_type', 'client_type_pk'];
            
            foreach ($indexColumns as $column) {
                if (Schema::hasColumn('kitchen_issue_master', $column)) {
                    try {
                        $table->index($column);
                    } catch (\Exception $e) {
                        // Index might already exist, continue
                    }
                }
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
        Schema::table('kitchen_issue_master', function (Blueprint $table) {
            // Drop new columns
            if (Schema::hasColumn('kitchen_issue_master', 'client_id')) {
                $table->dropColumn('client_id');
            }
            if (Schema::hasColumn('kitchen_issue_master', 'name_id')) {
                $table->dropColumn('name_id');
            }
            if (Schema::hasColumn('kitchen_issue_master', 'kitchen_issue_type')) {
                $table->dropColumn('kitchen_issue_type');
            }

            // Rename store_id back to inve_store_master_pk
            if (Schema::hasColumn('kitchen_issue_master', 'store_id') && 
                !Schema::hasColumn('kitchen_issue_master', 'inve_store_master_pk')) {
                $table->renameColumn('store_id', 'inve_store_master_pk');
            }

            // Restore old columns (basic structure, data will be lost)
            $table->unsignedBigInteger('inve_item_master_pk')->nullable()->comment('Item Master FK');
            $table->unsignedBigInteger('requested_store_id')->nullable()->comment('Requested Store ID');
            $table->decimal('quantity', 10, 2)->default(0)->comment('Item Quantity');
            $table->unsignedBigInteger('user_id')->nullable()->comment('Created by user');
            $table->unsignedBigInteger('store_employee_master_pk')->nullable()->comment('Store Employee FK');
            $table->dateTime('request_date')->nullable()->comment('Request Date');
            $table->decimal('unit_price', 10, 2)->default(0)->comment('Unit Price');
            $table->tinyInteger('transfer_to')->default(0)->comment('Transfer Type');
            $table->unsignedBigInteger('employee_student_pk')->nullable()->comment('Employee/Student FK');
            $table->string('bill_no')->nullable()->comment('Bill Number');
            $table->tinyInteger('send_for_approval')->default(0);
            $table->tinyInteger('notify_status')->default(0);
            $table->tinyInteger('approve_status')->default(0);
            $table->tinyInteger('paid_unpaid')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('modified_by')->nullable();
        });
    }
};
