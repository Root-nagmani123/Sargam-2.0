<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_master_seconds')
            && ! Schema::hasColumn('student_master_seconds', 'domicile_state_id')) {
            Schema::table('student_master_seconds', function (Blueprint $table) {
                $table->unsignedBigInteger('domicile_state_id')->nullable()->after('perm_pincode');
            });
        }

        if (! Schema::hasTable('fc_form_fields')) {
            return;
        }

        DB::table('fc_form_fields')
            ->where(function ($q) {
                $q->where('field_name', 'domicile_state')
                    ->orWhere('target_column', 'domicile_state');
            })
            ->update([
                'field_name'          => 'domicile_state_id',
                'field_type'          => 'select',
                'target_column'       => 'domicile_state_id',
                'validation_rules'    => 'required|exists:state_master,pk',
                'lookup_table'        => 'state_master',
                'lookup_value_column' => 'pk',
                'lookup_label_column' => 'state_name',
                'updated_at'          => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('fc_form_fields')) {
            return;
        }

        DB::table('fc_form_fields')
            ->where('field_name', 'domicile_state_id')
            ->update([
                'field_name'          => 'domicile_state',
                'field_type'          => 'text',
                'target_column'       => 'domicile_state',
                'validation_rules'    => 'required|string|max:100',
                'lookup_table'        => null,
                'lookup_value_column' => null,
                'lookup_label_column' => null,
                'updated_at'          => now(),
            ]);

        if (Schema::hasTable('student_master_seconds')
            && Schema::hasColumn('student_master_seconds', 'domicile_state_id')) {
            Schema::table('student_master_seconds', function (Blueprint $table) {
                $table->dropColumn('domicile_state_id');
            });
        }
    }
};
