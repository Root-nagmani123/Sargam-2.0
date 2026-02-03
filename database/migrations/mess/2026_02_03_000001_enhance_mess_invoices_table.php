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
        Schema::table('mess_invoices', function (Blueprint $table) {
            // Check and add columns if they don't exist
            if (!Schema::hasColumn('mess_invoices', 'invoice_no')) {
                $table->string('invoice_no')->unique()->after('id');
            }
            
            if (!Schema::hasColumn('mess_invoices', 'buyer_id')) {
                $table->unsignedBigInteger('buyer_id')->nullable()->after('vendor_id');
            }
            
            if (!Schema::hasColumn('mess_invoices', 'paid_amount')) {
                $table->decimal('paid_amount', 10, 2)->default(0)->after('amount');
            }
            
            if (!Schema::hasColumn('mess_invoices', 'balance')) {
                $table->decimal('balance', 10, 2)->default(0)->after('paid_amount');
            }
            
            if (!Schema::hasColumn('mess_invoices', 'payment_type')) {
                $table->string('payment_type')->default('cash')->after('balance');
            }
            
            if (!Schema::hasColumn('mess_invoices', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('payment_type');
            }
            
            if (!Schema::hasColumn('mess_invoices', 'due_date')) {
                $table->date('due_date')->nullable()->after('invoice_date');
            }
            
            if (!Schema::hasColumn('mess_invoices', 'paid_date')) {
                $table->date('paid_date')->nullable()->after('due_date');
            }
            
            if (!Schema::hasColumn('mess_invoices', 'remarks')) {
                $table->text('remarks')->nullable()->after('payment_status');
            }
            
            // Drop the old 'status' column if it exists
            if (Schema::hasColumn('mess_invoices', 'status') && Schema::hasColumn('mess_invoices', 'payment_status')) {
                $table->dropColumn('status');
            }
        });
        
        // Add indexes in a separate statement to avoid conflicts
        try {
            Schema::table('mess_invoices', function (Blueprint $table) {
                $table->index('buyer_id');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }
        
        try {
            Schema::table('mess_invoices', function (Blueprint $table) {
                $table->index('payment_status');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }
        
        try {
            Schema::table('mess_invoices', function (Blueprint $table) {
                $table->index('invoice_date');
            });
        } catch (\Exception $e) {
            // Index already exists, skip
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mess_invoices', function (Blueprint $table) {
            $table->dropColumn([
                'invoice_no',
                'buyer_id',
                'paid_amount',
                'balance',
                'payment_type',
                'payment_status',
                'due_date',
                'paid_date',
                'remarks'
            ]);
            
            $table->string('status')->after('amount');
        });
    }
};
