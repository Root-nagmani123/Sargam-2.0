<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Maps to salary_grade_master table from estate_module_tables SQL.
 * Used for estate eligibility criteria (pay scale / salary grade selection).
 */
class SalaryGrade extends Model
{
    protected $table = 'salary_grade_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'salary_grade',
        'grade_1',
        'grade_2',
        'grade_3',
    ];

    protected $casts = [
        'grade_1' => 'integer',
        'grade_2' => 'integer',
        'grade_3' => 'integer',
    ];

    protected $appends = ['display_label_text'];

    /**
     * Display label for dropdowns: salary_grade (pay scale) with grade pay when available.
     * grade_3 is typically used as grade pay in salary_grade_master.
     */
    public function getDisplayLabelTextAttribute(): string
    {
        $label = trim($this->salary_grade ?? '');
        $gradePay = $this->grade_3 ?? $this->grade_2 ?? $this->grade_1;
        if ($label === '') {
            return (string) $gradePay ?: '—';
        }
        if ($gradePay !== null && $gradePay !== '') {
            $label .= ' (GP ' . $gradePay . ')';
        }
        return $label;
    }
}
