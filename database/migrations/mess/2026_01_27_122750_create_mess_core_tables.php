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
        // Vendors Table
        Schema::create('mess_vendors', function (Blueprint $table) {
            $table->id();
            $table->string('vendor_name');
            $table->string('vendor_code')->unique();
            $table->string('contact_person')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('pan_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('vendor_code');
            $table->index('is_active');
        });

        // Item Categories Table
        Schema::create('mess_item_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name');
            $table->string('category_code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('category_code');
        });

        // Item Subcategories Table
        Schema::create('mess_item_subcategories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('subcategory_name');
            $table->string('subcategory_code')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('category_id');
            $table->index('subcategory_code');
        });

        // Inventories Table
        Schema::create('mess_inventories', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->string('item_code')->unique();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('subcategory_id')->nullable();
            $table->string('unit_of_measurement')->default('kg');
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('minimum_stock', 10, 2)->default(0);
            $table->decimal('current_stock', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('item_code');
            $table->index('category_id');
            $table->index('subcategory_id');
            $table->index('is_active');
        });

        // Stores Table
        Schema::create('mess_stores', function (Blueprint $table) {
            $table->id();
            $table->string('store_name');
            $table->string('store_code')->unique();
            $table->string('store_type')->default('mess'); // mess, canteen, godown
            $table->string('location')->nullable();
            $table->unsignedBigInteger('incharge_user_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('store_code');
            $table->index('store_type');
        });

        // Material Requests Table
        Schema::create('mess_material_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('requested_by');
            $table->date('request_date');
            $table->date('required_date')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected, completed
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            
            $table->index('request_number');
            $table->index('store_id');
            $table->index('status');
        });

        // Material Request Items Table
        Schema::create('mess_material_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_request_id');
            $table->unsignedBigInteger('inventory_id');
            $table->decimal('requested_quantity', 10, 2);
            $table->decimal('approved_quantity', 10, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            $table->index('material_request_id');
            $table->index('inventory_id');
        });

        // Purchase Orders Table
        Schema::create('mess_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('material_request_id')->nullable();
            $table->date('po_date');
            $table->date('expected_delivery_date')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('status')->default('pending'); // pending, approved, completed, cancelled
            $table->text('terms_conditions')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            $table->index('po_number');
            $table->index('vendor_id');
            $table->index('status');
        });

        // Purchase Order Items Table
        Schema::create('mess_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('inventory_id');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->decimal('received_quantity', 10, 2)->default(0);
            $table->timestamps();
            
            $table->index('purchase_order_id');
            $table->index('inventory_id');
        });

        // Inbound Transactions Table (Goods Receipt)
        Schema::create('mess_inbound_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->unsignedBigInteger('purchase_order_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('store_id');
            $table->date('receipt_date');
            $table->string('invoice_number')->nullable();
            $table->date('invoice_date')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->unsignedBigInteger('received_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            $table->index('transaction_number');
            $table->index('purchase_order_id');
            $table->index('store_id');
        });

        // Inbound Transaction Items Table
        Schema::create('mess_inbound_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inbound_transaction_id');
            $table->unsignedBigInteger('inventory_id');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            
            $table->index('inbound_transaction_id');
            $table->index('inventory_id');
        });

        // Invoices Table
        Schema::create('mess_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('store_id');
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->string('payment_status')->default('unpaid'); // unpaid, partial, paid
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            $table->index('invoice_number');
            $table->index('payment_status');
        });

        // Meal Mappings Table
        Schema::create('mess_meal_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('meal_type'); // breakfast, lunch, dinner, snacks
            $table->unsignedBigInteger('inventory_id');
            $table->decimal('standard_quantity', 10, 2);
            $table->integer('persons')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('meal_type');
            $table->index('inventory_id');
        });

        // Store Allocations Table
        Schema::create('mess_store_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('store_id');
            $table->string('role')->default('user'); // admin, manager, user
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('store_id');
        });

        // Permission Settings Table
        Schema::create('mess_permission_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('permission_type');
            $table->string('permission_value');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mess_permission_settings');
        Schema::dropIfExists('mess_store_allocations');
        Schema::dropIfExists('mess_meal_mappings');
        Schema::dropIfExists('mess_invoices');
        Schema::dropIfExists('mess_inbound_transaction_items');
        Schema::dropIfExists('mess_inbound_transactions');
        Schema::dropIfExists('mess_purchase_order_items');
        Schema::dropIfExists('mess_purchase_orders');
        Schema::dropIfExists('mess_material_request_items');
        Schema::dropIfExists('mess_material_requests');
        Schema::dropIfExists('mess_stores');
        Schema::dropIfExists('mess_inventories');
        Schema::dropIfExists('mess_item_subcategories');
        Schema::dropIfExists('mess_item_categories');
        Schema::dropIfExists('mess_vendors');
    }
};
