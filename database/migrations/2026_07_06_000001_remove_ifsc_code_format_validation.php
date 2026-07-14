<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Drop the IFSC-format regex from every bank IFSC field so any bank code is accepted.
 * The field stays required (required|string|max:20) — only the strict
 * regex:/^[A-Z]{4}0[A-Z0-9]{6}$/ format check is removed.
 */
return new class extends Migration
{
    private const IFSC_REGEX = 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/';

    public function up(): void
    {
        foreach ($this->ifscFields() as $row) {
            // Strip the regex: rule (IFSC pattern has no '|', so [^|]* is safe).
            $rules = preg_replace('/\|?\bregex:[^|]*/', '', (string) $row->validation_rules) ?? (string) $row->validation_rules;
            $rules = trim((string) preg_replace('/\|{2,}/', '|', $rules), '|');

            DB::table('fc_form_fields')->where('id', $row->id)->update(['validation_rules' => $rules]);
        }
    }

    public function down(): void
    {
        foreach ($this->ifscFields() as $row) {
            $rules = (string) $row->validation_rules;
            if (! str_contains($rules, 'regex:')) {
                $rules = $rules === '' ? self::IFSC_REGEX : $rules.'|'.self::IFSC_REGEX;
            }

            DB::table('fc_form_fields')->where('id', $row->id)->update(['validation_rules' => $rules]);
        }
    }

    private function ifscFields()
    {
        return DB::table('fc_form_fields')
            ->where('field_type', 'text')
            ->where(function ($q) {
                $q->where('field_name', 'ifsc_code')->orWhere('target_column', 'ifsc_code');
            })
            ->get(['id', 'validation_rules']);
    }
};
