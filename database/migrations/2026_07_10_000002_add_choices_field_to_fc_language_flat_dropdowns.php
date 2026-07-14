<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Make the flat "Language Details" dropdowns (Descriptive Roll step) Choices.js
     * searchable dropdowns by adding the `choices-field` marker class. The step-fields
     * view initialises Choices on any `.choices-field`.
     */
    private array $fields = [
        'mother_tongue',          // Mother Tongue
        'medium_12th',            // Medium in Class 12
        'medium_graduation',      // Medium in Graduation
        'medium_civil_service',   // Medium in Civil Service Exam
        'viva_language',          // Language of Civil Service Exam Viva / Interview
    ];

    public function up(): void
    {
        $this->applyClass('choices-field');
    }

    public function down(): void
    {
        $this->applyClass(null);
    }

    private function applyClass(?string $add): void
    {
        $rows = DB::table('fc_form_fields')
            ->whereIn('field_name', $this->fields)
            ->where('field_type', 'select')
            ->get(['id', 'css_class']);

        foreach ($rows as $row) {
            $tokens = array_values(array_filter(
                preg_split('/\s+/', (string) $row->css_class, -1, PREG_SPLIT_NO_EMPTY),
                fn ($t) => $t !== 'choices-field' && $t !== 'select2-field'
            ));
            if ($add !== null) {
                $tokens[] = $add;
            }
            DB::table('fc_form_fields')
                ->where('id', $row->id)
                ->update(['css_class' => implode(' ', $tokens)]);
        }
    }
};
