<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('student_masters', 'travel_done')) {
            Schema::table('student_masters', function (Blueprint $table) {
                $table->tinyInteger('travel_done')->default(0)->after('bank_done');
            });
        }

        DB::table('student_masters')->where('docs_done', 1)->update(['travel_done' => 1]);

        if (! Schema::hasColumn('travel_type_masters', 'is_active')) {
            Schema::table('travel_type_masters', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('travel_type_name');
            });
        }

        if (! Schema::hasColumn('mctp_travel_mode_masters', 'is_active')) {
            Schema::table('mctp_travel_mode_masters', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('travel_mode_name');
            });
        }

        if (! Schema::hasColumn('pick_up_drop_type_masters', 'is_active')) {
            Schema::table('pick_up_drop_type_masters', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('type_name');
            });
        }

        if (! Schema::hasColumn('student_travel_plan_masters', 'joining_date')) {
            Schema::table('student_travel_plan_masters', function (Blueprint $table) {
                $table->date('joining_date')->nullable()->after('username');
                $table->time('joining_time')->nullable()->after('joining_date');
                $table->boolean('needs_pickup')->default(false)->after('pickup_required');
                $table->string('pickup_from_location', 200)->nullable()->after('pickup_type_id');
                $table->dateTime('pickup_datetime')->nullable()->after('pickup_from_location');
                $table->boolean('needs_drop')->default(false)->after('pickup_datetime');
                $table->unsignedBigInteger('drop_type_id')->nullable()->after('needs_drop');
                $table->string('drop_to_location', 200)->nullable()->after('drop_type_id');
                $table->dateTime('drop_datetime')->nullable()->after('drop_to_location');
                $table->string('departure_city', 150)->nullable()->after('drop_datetime');
                $table->string('departure_state', 150)->nullable()->after('departure_city');
                $table->text('special_requirements')->nullable()->after('departure_state');
                $table->boolean('is_submitted')->default(false)->after('special_requirements');
            });
        }

        if (Schema::hasColumn('student_travel_plan_masters', 'drop_type_id')
            && ! $this->foreignKeyExists('student_travel_plan_masters', 'student_travel_plan_masters_drop_type_id_foreign')) {
            Schema::table('student_travel_plan_masters', function (Blueprint $table) {
                $table->foreign('drop_type_id')->references('id')->on('pick_up_drop_type_masters')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('mctp_student_travel_plan_details', 'travel_plan_id')) {
            Schema::table('mctp_student_travel_plan_details', function (Blueprint $table) {
                $table->unsignedBigInteger('travel_plan_id')->nullable()->after('username');
                $table->unsignedInteger('leg_number')->nullable()->after('travel_plan_id');
                $table->unsignedBigInteger('travel_mode_id')->nullable()->after('to_station');
                $table->string('train_flight_name', 200)->nullable()->after('train_flight_no');
                $table->string('class_of_travel', 50)->nullable()->after('train_flight_name');
                $table->decimal('ticket_amount', 10, 2)->nullable()->after('pnr_ticket_no');
                $table->boolean('is_entitled')->default(true)->after('ticket_amount');
                $table->text('remarks')->nullable()->after('is_entitled');
            });
        }

        if (Schema::hasColumn('mctp_student_travel_plan_details', 'travel_plan_id')
            && ! $this->foreignKeyExists('mctp_student_travel_plan_details', 'mctp_student_travel_plan_details_travel_plan_id_foreign')) {
            Schema::table('mctp_student_travel_plan_details', function (Blueprint $table) {
                $table->foreign('travel_plan_id')->references('id')->on('student_travel_plan_masters')->cascadeOnDelete();
            });
        }

        if (Schema::hasColumn('mctp_student_travel_plan_details', 'travel_mode_id')
            && ! $this->foreignKeyExists('mctp_student_travel_plan_details', 'mctp_student_travel_plan_details_travel_mode_id_foreign')) {
            Schema::table('mctp_student_travel_plan_details', function (Blueprint $table) {
                $table->foreign('travel_mode_id')->references('id')->on('mctp_travel_mode_masters')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if ($this->foreignKeyExists('mctp_student_travel_plan_details', 'mctp_student_travel_plan_details_travel_plan_id_foreign')) {
            Schema::table('mctp_student_travel_plan_details', function (Blueprint $table) {
                $table->dropForeign(['travel_plan_id']);
            });
        }
        if ($this->foreignKeyExists('mctp_student_travel_plan_details', 'mctp_student_travel_plan_details_travel_mode_id_foreign')) {
            Schema::table('mctp_student_travel_plan_details', function (Blueprint $table) {
                $table->dropForeign(['travel_mode_id']);
            });
        }

        if (Schema::hasColumn('mctp_student_travel_plan_details', 'travel_plan_id')) {
            Schema::table('mctp_student_travel_plan_details', function (Blueprint $table) {
                $table->dropColumn([
                    'travel_plan_id', 'leg_number', 'travel_mode_id',
                    'train_flight_name', 'class_of_travel', 'ticket_amount', 'is_entitled', 'remarks',
                ]);
            });
        }

        if ($this->foreignKeyExists('student_travel_plan_masters', 'student_travel_plan_masters_drop_type_id_foreign')) {
            Schema::table('student_travel_plan_masters', function (Blueprint $table) {
                $table->dropForeign(['drop_type_id']);
            });
        }

        if (Schema::hasColumn('student_travel_plan_masters', 'joining_date')) {
            Schema::table('student_travel_plan_masters', function (Blueprint $table) {
                $table->dropColumn([
                    'joining_date', 'joining_time', 'needs_pickup', 'pickup_from_location', 'pickup_datetime',
                    'needs_drop', 'drop_type_id', 'drop_to_location', 'drop_datetime',
                    'departure_city', 'departure_state', 'special_requirements', 'is_submitted',
                ]);
            });
        }

        if (Schema::hasColumn('pick_up_drop_type_masters', 'is_active')) {
            Schema::table('pick_up_drop_type_masters', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('mctp_travel_mode_masters', 'is_active')) {
            Schema::table('mctp_travel_mode_masters', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('travel_type_masters', 'is_active')) {
            Schema::table('travel_type_masters', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('student_masters', 'travel_done')) {
            Schema::table('student_masters', function (Blueprint $table) {
                $table->dropColumn('travel_done');
            });
        }
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        $conn = Schema::getConnection();
        $database = $conn->getDatabaseName();

        return (bool) $conn->selectOne(
            'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$database, $table, $name, 'FOREIGN KEY']
        );
    }
};
