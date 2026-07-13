<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('medical_case_master')) {
            Schema::create('medical_case_master', function (Blueprint $table) {
                $table->bigIncrements('pk');
                $table->string('case_name', 100)->nullable();
                $table->integer('active_inactive')->default(1);
                $table->timestamp('created_date')->useCurrent();
                $table->timestamp('modified_date')->useCurrent()->useCurrentOnUpdate();
            });
        }

        $cases = ['IPD', 'OPD', 'After OPD', 'Referral', 'PT Exemption'];

        foreach ($cases as $name) {
            $exists = DB::table('medical_case_master')->where('case_name', $name)->exists();
            if (! $exists) {
                DB::table('medical_case_master')->insert([
                    'case_name'       => $name,
                    'active_inactive' => 1,
                    'created_date'    => now(),
                    'modified_date'   => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_case_master');
    }
};
