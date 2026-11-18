<?php

namespace App\Models\COE;

use App\Models\StudentMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One answer sheet as the OMR scanner read it.
 *
 * `answers` is the list of marked options in question order: 'A'..'Z', '' for a
 * blank, '*' for more than one bubble marked.
 */
class OmrResponse extends Model
{
    public const BLANK = '';

    public const MULTI_MARKED = '*';

    protected $table = 'coe_omr_responses';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'coe_omr_upload_pk',
        'row_no',
        'ncs_header',
        'barcode',
        'roll_number',
        'student_master_pk',
        'match_remarks',
        'booklet_series',
        'answers',
        'attempted_count',
        'blank_count',
        'multi_marked_count',
        'score',
        'has_error',
        'error_remarks',
        'created_date',
    ];

    protected $casts = [
        'coe_omr_upload_pk' => 'integer',
        'row_no' => 'integer',
        'student_master_pk' => 'integer',
        'answers' => 'array',
        'attempted_count' => 'integer',
        'blank_count' => 'integer',
        'multi_marked_count' => 'integer',
        'score' => 'integer',
        'has_error' => 'boolean',
        'created_date' => 'datetime',
    ];

    public function upload(): BelongsTo
    {
        return $this->belongsTo(OmrUpload::class, 'coe_omr_upload_pk', 'pk');
    }

    /** NULL until the ENR_NO matched exactly one participant (OmrParticipantMatcher). */
    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentMaster::class, 'student_master_pk', 'pk');
    }
}
