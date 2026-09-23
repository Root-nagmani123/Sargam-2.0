<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('protocol_vehicle_masters', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Vehicle Information
            |--------------------------------------------------------------------------
            */
            $table->string('vehicle_name');
            $table->string('vehicle_number')->unique();
            $table->string('manufacturer');
            $table->string('engine_number')->unique();
            $table->string('chassis_number')->unique();
            $table->string('model');

            $table->decimal('average_committed', 8, 2)->nullable();
            $table->decimal('current_reading', 12, 2)->default(0);

            $table->string('fuel_type');
            $table->string('vehicle_type');

            $table->unsignedInteger('capacity');

            $table->boolean('status')->default(true);

            $table->unsignedSmallInteger('manufacture_year')->nullable();

            $table->string('maintained_by');

            $table->string('registration_number');

            $table->unsignedBigInteger('country_id');
            $table->unsignedBigInteger('state_id');
            $table->unsignedBigInteger('city_id');

            $table->string('driver_id');
            $table->string('registration_authority');

            $table->text('vehicle_notes')->nullable();
            $table->string('vehicle_part')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Validity
            |--------------------------------------------------------------------------
            */
            $table->decimal('validity_km', 12, 2)->nullable();
            $table->unsignedInteger('validity_years');

            /*
            |--------------------------------------------------------------------------
            | Financial Information
            |--------------------------------------------------------------------------
            */
            $table->string('owned_by')->nullable();
            $table->string('financed_by')->nullable();

            $table->decimal('total_vehicle_cost', 15, 2)->nullable();
            $table->decimal('actual_vehicle_cost', 15, 2)->nullable();

            $table->enum('purchase_type', [
                'purchase',
                'rent',
            ])->default('purchase');

            $table->date('purchase_date')->nullable();

            $table->decimal('purchase_cost', 15, 2)->nullable();
            $table->decimal('margin_money', 15, 2)->nullable();
            $table->decimal('loan_amount', 15, 2)->nullable();
            $table->decimal('emi', 15, 2)->nullable();

            $table->decimal('rate_of_interest', 5, 2)->nullable();

            $table->date('first_installment_date')->nullable();
            $table->date('last_installment_date')->nullable();

            $table->string('vendor_name')->nullable();

            $table->text('purchase_notes')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Insurance Information
            |--------------------------------------------------------------------------
            */
            $table->boolean('has_insurance')->default(false);

            $table->string('insurer_name')->nullable();
            $table->string('insurance_company_name')->nullable();

            $table->date('insurance_date')->nullable();
            $table->date('insurance_expiry_date')->nullable();

            $table->decimal('premium_amount', 15, 2)->nullable();

            $table->text('insurance_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('protocol_vehicle_masters');
    }
};