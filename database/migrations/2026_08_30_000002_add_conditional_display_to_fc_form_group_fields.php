<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Conditional display for group fields, configurable from the Form Builder.
 *
 * Until now "show this field only when another field says X" lived in a hard-coded map
 * inside dynamic-group-row.blade.php (Spouse Name ← spouse_in_cse = Yes), so every new
 * conditional question needed a deploy. These two columns move the rule into the form
 * definition, where an admin can set it:
 *
 *   condition_field — field_name of ANOTHER field in the same group
 *   condition_value — the value that field must hold for this one to be shown
 *
 * Both null (the default) means the field is always shown, which is every existing field.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fc_form_group_fields')) {
            return;
        }

        Schema::table('fc_form_group_fields', function (Blueprint $table) {
            if (! Schema::hasColumn('fc_form_group_fields', 'condition_field')) {
                $table->string('condition_field', 100)->nullable()->after('css_class');
            }
            if (! Schema::hasColumn('fc_form_group_fields', 'condition_value')) {
                $table->string('condition_value', 200)->nullable()->after('condition_field');
            }
        });

        // Carry over the one rule that used to be hard-coded in the blade, so the
        // Spouse Name dropdown keeps hiding when the spouse question is answered "No".
        $spouseGroupIds = DB::table('fc_form_field_groups')->where('group_name', 'spouse')->pluck('id');

        if ($spouseGroupIds->isNotEmpty()) {
            DB::table('fc_form_group_fields')
                ->where('field_name', 'spouse_name')
                ->whereIn('group_id', $spouseGroupIds)
                ->update([
                    'condition_field' => 'spouse_in_cse',
                    'condition_value' => 'Yes',
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('fc_form_group_fields')) {
            return;
        }

        Schema::table('fc_form_group_fields', function (Blueprint $table) {
            foreach (['condition_field', 'condition_value'] as $column) {
                if (Schema::hasColumn('fc_form_group_fields', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
