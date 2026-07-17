<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Raise the OT photo and signature upload limit to 2 MB (2048 KB) across every
 * dynamic registration form. file_max_kb is the single source of truth — it drives
 * the client-side data-max-kb check, the server-side max: rule (resolveFileMaxKb),
 * and the "max … MB" hint. The stored validation_rules max: is kept in sync too.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->setMax(['photo', 'signature'], ['photo_path', 'signature_path'], 2048);
    }

    public function down(): void
    {
        // Restore the previous per-type limits.
        $this->setMax(['photo'], ['photo_path'], 500);
        $this->setMax(['signature'], ['signature_path'], 200);
    }

    /**
     * @param  array<int, string>  $fieldNames
     * @param  array<int, string>  $targetColumns
     */
    private function setMax(array $fieldNames, array $targetColumns, int $kb): void
    {
        $rows = DB::table('fc_form_fields')
            ->where('field_type', 'file')
            ->where(function ($q) use ($fieldNames, $targetColumns) {
                $q->whereIn('field_name', $fieldNames)
                    ->orWhereIn('target_column', $targetColumns);
            })
            ->get(['id', 'validation_rules']);

        foreach ($rows as $row) {
            $rules = (string) $row->validation_rules;
            if (preg_match('/max:\d+/', $rules)) {
                $rules = preg_replace('/max:\d+/', 'max:'.$kb, $rules) ?? $rules;
            } elseif ($rules !== '') {
                $rules .= '|max:'.$kb;
            } else {
                $rules = 'max:'.$kb;
            }

            DB::table('fc_form_fields')->where('id', $row->id)->update([
                'file_max_kb'      => $kb,
                'validation_rules' => $rules,
            ]);
        }
    }
};
