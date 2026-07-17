<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Turn the "Spouse Name" field into a searchable dropdown of registered candidates
 * (fc_registration_master.display_name). It's rendered with Choices.js (the .choices-field
 * marker class) and, in the blade, is only shown when "Is your spouse also registering?" = Yes.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->spouseNameFields() as $f) {
            DB::table('fc_form_group_fields')->where('id', $f->id)->update([
                'field_type'          => 'select',
                'lookup_table'        => 'fc_registration_master',
                'lookup_value_column' => 'display_name',
                'lookup_label_column' => 'display_name',
                'css_class'           => $this->withMarker($f->css_class),
            ]);
        }
    }

    public function down(): void
    {
        foreach ($this->spouseNameFields() as $f) {
            $css = trim((string) preg_replace('/\s*\b(choices-field|select2-field)\b/', '', (string) $f->css_class));

            DB::table('fc_form_group_fields')->where('id', $f->id)->update([
                'field_type'          => 'text',
                'lookup_table'        => null,
                'lookup_value_column' => null,
                'lookup_label_column' => null,
                'css_class'           => $css,
            ]);
        }
    }

    private function withMarker(?string $css): string
    {
        $css = trim((string) preg_replace('/\s*\bselect2-field\b/', '', (string) $css));
        if ($css === '') {
            $css = 'col-md-6';
        }
        if (! str_contains($css, 'choices-field')) {
            $css = trim($css.' choices-field');
        }

        return $css;
    }

    private function spouseNameFields()
    {
        $spouseGroupIds = DB::table('fc_form_field_groups')->where('group_name', 'spouse')->pluck('id');

        return DB::table('fc_form_group_fields')
            ->where('field_name', 'spouse_name')
            ->whereIn('group_id', $spouseGroupIds)
            ->get(['id', 'css_class']);
    }
};
