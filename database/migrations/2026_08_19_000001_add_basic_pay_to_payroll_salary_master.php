<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // payroll_salary_master already exists (created outside Laravel migrations) and is read
        // by the Estate module — this only adds the one column the new Member wizard step needs.
        if (! Schema::hasColumn('payroll_salary_master', 'basic_pay')) {
            Schema::table('payroll_salary_master', function (Blueprint $table) {
                $table->integer('basic_pay')->nullable()->after('employee_category_master_pk');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_salary_master', 'basic_pay')) {
            Schema::table('payroll_salary_master', function (Blueprint $table) {
                $table->dropColumn('basic_pay');
            });
        }
    }
};
