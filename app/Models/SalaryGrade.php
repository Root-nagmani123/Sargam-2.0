<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryGrade extends Model
{
    protected $table = 'salary_grade_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = ['salary_grade'];

    protected $appends = ['display_label_text'];

    public function getDisplayLabelTextAttribute(): string
    {
        return $this->salary_grade ?? '';
    }
}
