<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EligibilityCriterion extends Model
{
    protected $table = 'estate_eligibility_mapping';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    // salary_grade_master_pk maps to salary_grade_master.pk
    protected $fillable = ['salary_grade_master_pk', 'estate_unit_type_master_pk', 'estate_unit_sub_type_master_pk'];

    /**
     * Backwards-compatible accessor name: payScale (actually salary grade).
     */
    public function payScale(): BelongsTo
    {
        return $this->belongsTo(SalaryGrade::class, 'salary_grade_master_pk', 'pk');
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class, 'estate_unit_type_master_pk', 'pk');
    }

    public function unitSubType(): BelongsTo
    {
        return $this->belongsTo(UnitSubType::class, 'estate_unit_sub_type_master_pk', 'pk');
    }
}
