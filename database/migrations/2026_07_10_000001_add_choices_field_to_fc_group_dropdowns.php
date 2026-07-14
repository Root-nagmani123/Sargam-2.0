<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Turn these dynamic group dropdowns into Choices.js searchable dropdowns by adding
     * the `choices-field` marker class (the front-end inits Choices on `.choices-field`).
     * Any existing `select2-field` on the same field is swapped out so only one widget inits.
     */
    private array $fields = [
        'language_id',              // Language
        'qualification_id',         // Degree
        'board_id',                 // University / Board Name
        'highest_stream_id',        // Highest Qualification Stream
        'previous_service_id',      // Previous service joined from a previous attempt
        'optonal_subject_first',    // Optional Subject First
        'optional_subject_second',  // Optional Subject Second
    ];

    public function up(): void
    {
        $rows = DB::table('fc_form_group_fields')
            ->whereIn('field_name', $this->fields)
            ->where('field_type', 'select')
            ->get(['id', 'css_class']);

        foreach ($rows as $row) {
            DB::table('fc_form_group_fields')
                ->where('id', $row->id)
                ->update(['css_class' => $this->withClass((string) $row->css_class, ['select2-field'], 'choices-field')]);
        }
    }

    public function down(): void
    {
        $rows = DB::table('fc_form_group_fields')
            ->whereIn('field_name', $this->fields)
            ->where('field_type', 'select')
            ->get(['id', 'css_class']);

        foreach ($rows as $row) {
            DB::table('fc_form_group_fields')
                ->where('id', $row->id)
                ->update(['css_class' => $this->withClass((string) $row->css_class, ['choices-field'], null)]);
        }
    }

    /** Remove $remove classes, then append $add (if given), de-duplicated, order preserved. */
    private function withClass(string $css, array $remove, ?string $add): string
    {
        $drop = array_merge($remove, $add !== null ? [$add] : []);
        $tokens = array_values(array_filter(
            preg_split('/\s+/', $css, -1, PREG_SPLIT_NO_EMPTY),
            fn ($t) => ! in_array($t, $drop, true)
        ));
        if ($add !== null) {
            $tokens[] = $add;
        }

        return implode(' ', $tokens);
    }
};
