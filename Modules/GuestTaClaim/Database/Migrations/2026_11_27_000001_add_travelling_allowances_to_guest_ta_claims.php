<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gtc_claims', function (Blueprint $table) {
            $table->text('faculty_official_address')->nullable()->after('faculty_organisation');
            $table->decimal('basic_pay', 12, 2)->nullable()->after('faculty_category');
            $table->unsignedSmallInteger('da_journey_days')->default(0)->after('da_days');
            $table->unsignedSmallInteger('da_stay_days')->default(0)->after('da_journey_days');
            $table->unsignedTinyInteger('da_percentage')->default(100)->after('da_rate');
            $table->string('da_rate_basis', 16)->nullable()->after('da_percentage');
            $table->text('admissible_journey_numbers')->nullable()->after('da_rate_basis');
            $table->text('higher_class_journey_numbers')->nullable()->after('admissible_journey_numbers');
            $table->string('higher_class_reimbursement_basis', 32)->nullable()->after('higher_class_journey_numbers');
        });

        DB::table('gtc_claims')->update(['da_journey_days' => DB::raw('da_days')]);

        Schema::create('gtc_claim_travelling_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained('gtc_claims')->cascadeOnDelete();
            $table->dateTime('departure_at')->nullable();
            $table->string('departure_from', 200)->nullable();
            $table->dateTime('arrival_at')->nullable();
            $table->string('arrival_to', 200)->nullable();
            $table->string('travel_mode', 64)->nullable();
            $table->decimal('distance_km', 10, 2)->nullable();
            $table->decimal('fare_paid', 12, 2)->default(0);
            $table->decimal('fare_admissible', 12, 2)->default(0);
            $table->decimal('accommodation', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gtc_claim_travelling_allowances');

        Schema::table('gtc_claims', function (Blueprint $table) {
            $table->dropColumn([
                'faculty_official_address',
                'basic_pay',
                'da_journey_days',
                'da_stay_days',
                'da_percentage',
                'da_rate_basis',
                'admissible_journey_numbers',
                'higher_class_journey_numbers',
                'higher_class_reimbursement_basis',
            ]);
        });
    }
};
