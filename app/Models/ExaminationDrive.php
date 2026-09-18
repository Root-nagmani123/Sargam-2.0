<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExaminationDrive extends Model
{
    protected $table = 'examination_drives';
    protected $guarded = [];

    public $timestamps = false;

    public const STATUS_DRAFT = 0;
    public const STATUS_PUBLISHED = 1;
    public const STATUS_CLOSED = 2;

    public const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PUBLISHED => 'Published',
        self::STATUS_CLOSED => 'Closed',
    ];

    public const PHASES = ['Phase-I', 'Phase-II', 'Phase-III', 'Phase-IV'];

    public function examinationType()
    {
        return $this->belongsTo(ExaminationTypeMaster::class, 'examination_type_master_pk', 'pk');
    }

    public function term()
    {
        return $this->belongsTo(TermMaster::class, 'term_master_pk', 'pk');
    }

    public function course()
    {
        return $this->belongsTo(CourseMaster::class, 'course_master_pk', 'pk');
    }
}
