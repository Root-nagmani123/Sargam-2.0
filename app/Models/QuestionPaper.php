<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One question paper per subject on an examination drive (COE). Created when the
 * faculty first adds the paper; until then the subject shows as Pending at Faculty
 * from the drive's faculty mapping alone.
 */
class QuestionPaper extends Model
{
    use SoftDeletes;

    // The lifecycle. Each screen maps these onto its own badge keys (QuestionPaperService,
    // RajbhashaPaperService), since those keys are also the views' CSS classes.
    public const STATUS_PENDING_FACULTY = 'pending-faculty';     // nothing uploaded yet (no row)

    public const STATUS_PENDING_RAJBHASHA = 'pending-rajbhasha'; // faculty uploaded it, with the translator

    public const STATUS_TRANSLATED = 'translated';               // translation uploaded, not yet sent

    public const STATUS_SENT_FOR_APPROVAL = 'sent-for-approval'; // back with the faculty to verify

    public const STATUS_PUBLISHED = 'published';                 // faculty verified and froze it

    /** Papers live on this private disk and are only ever served through a controller. */
    public const DISK = 'local';

    public const CREATED_AT = 'created_date';

    public const UPDATED_AT = 'modified_date';

    protected $table = 'question_papers';

    protected $guarded = [];

    protected $casts = [
        'deadline' => 'date',
        'original_uploaded_at' => 'datetime',
        'frozen_at' => 'datetime',
        'translated_at' => 'datetime',
        'sent_for_approval_at' => 'datetime',
    ];

    public function drive(): BelongsTo
    {
        return $this->belongsTo(ExaminationDrive::class, 'examination_drive_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(SubjectMaster::class, 'subject_master_pk', 'pk');
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(FacultyMaster::class, 'faculty_master_pk', 'pk');
    }

    public function translator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'translated_by', 'pk');
    }
}
