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
        // Create sales transactions table (main sale/bill table)
        Schema::create('mess_sales_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('bill_number')->unique();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('buyer_id');
            $table->integer('buyer_type'); // 2=OT, 3=Section, 4=Guest, 5=Employee, 6=Other
            $table->string('buyer_name')->nullable(); // For 'Other' type buyers
            $table->date('sale_date');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('due_amount', 10, 2)->default(0);
            $table->string('payment_mode')->default('cash'); // cash, cheque, credit
            $table->integer('payment_type'); // 1=Paid, 2=Credit
            $table->integer('paid_unpaid')->default(0); // 0=Unpaid, 1=Paid
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            $table->index('buyer_id');
            $table->index('store_id');
            $table->index('sale_date');
            $table->index('payment_type');
            
            $table->foreign('store_id')->references('id')->on('mess_stores')->onDelete('cascade');
            $table->foreign('created_by')->references('pk')->on('user_credentials')->onDelete('cascade');
        });
        
        // Create sales transaction items table (sale mapping)
        Schema::create('mess_sales_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_transaction_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('quantity', 10, 2);
            $table->decimal('rate', 10, 2);
            $table->decimal('amount', 10, 2);
            $table->timestamps();
            
            $table->index('sale_transaction_id');
            $table->index('item_id');
            
            $table->foreign('sale_transaction_id')->references('id')->on('mess_sales_transactions')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('mess_inventories')->onDelete('cascade');
        });
        
        // Create buyer credit limits table
        Schema::create('mess_buyer_credit_limits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('buyer_id');
            $table->integer('buyer_type');
            $table->decimal('max_limit', 10, 2);
            $table->decimal('used_amount', 10, 2)->default(0);
            $table->decimal('available_limit', 10, 2);
            $table->timestamps();
            
            $table->index('buyer_id');
            $table->index('buyer_type');
        });
        
        // Create payment history table
        Schema::create('mess_payment_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_transaction_id');
            $table->decimal('payment_amount', 10, 2);
            $table->date('payment_date');
            $table->string('payment_mode'); // cash, cheque, online
            $table->string('cheque_number')->nullable();
            $table->string('reference_number')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('received_by');
            $table->timestamps();
            
            $table->index('sale_transaction_id');
            $table->index('payment_date');
            
            $table->foreign('sale_transaction_id')->references('id')->on('mess_sales_transactions')->onDelete('cascade');
            $table->foreign('received_by')->references('pk')->on('user_credentials')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mess_payment_history');
        Schema::dropIfExists('mess_buyer_credit_limits');
        Schema::dropIfExists('mess_sales_transaction_items');
        Schema::dropIfExists('mess_sales_transactions');
    }
};
