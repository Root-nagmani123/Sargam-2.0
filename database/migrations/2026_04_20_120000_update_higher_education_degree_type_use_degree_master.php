<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fc_form_group_fields')
            || ! Schema::hasTable('fc_form_field_groups')
            || ! Schema::hasTable('fc_form_steps')) {
            return;
        }

        $ids = DB::table('fc_form_group_fields as gf')
            ->join('fc_form_field_groups as g', 'g.id', '=', 'gf.group_id')
            ->join('fc_form_steps as s', 's.id', '=', 'g.step_id')
            ->where('s.step_slug', 'step3')
            ->where('g.group_name', 'higher_education')
            ->where('gf.field_name', 'degree_type')
            ->pluck('gf.id');

        foreach ($ids as $id) {
            DB::table('fc_form_group_fields')->where('id', $id)->update([
                'options_json'          => null,
                'lookup_table'          => 'degree_master',
                'lookup_value_column'   => 'pk',
                'lookup_label_column'   => 'degree_name',
                'validation_rules'      => 'required|exists:degree_master,pk',
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('fc_form_group_fields')) {
            return;
        }

        $ids = DB::table('fc_form_group_fields as gf')
            ->join('fc_form_field_groups as g', 'g.id', '=', 'gf.group_id')
            ->join('fc_form_steps as s', 's.id', '=', 'g.step_id')
            ->where('s.step_slug', 'step3')
            ->where('g.group_name', 'higher_education')
            ->where('gf.field_name', 'degree_type')
            ->pluck('gf.id');

        $options = json_encode([
            ['value' => 'PG', 'label' => 'Post Graduate'],
            ['value' => 'PhD', 'label' => 'PhD'],
            ['value' => 'PG Diploma', 'label' => 'PG Diploma'],
        ]);

        foreach ($ids as $id) {
            DB::table('fc_form_group_fields')->where('id', $id)->update([
                'options_json'          => $options,
                'lookup_table'          => null,
                'lookup_value_column'   => null,
                'lookup_label_column'   => null,
                'validation_rules'      => 'required|string|max:100',
            ]);
        }
    }
};
