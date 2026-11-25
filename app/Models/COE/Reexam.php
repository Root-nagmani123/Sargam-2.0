<?php

namespace App\Models\COE;

use App\Models\ExaminationDrive;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A re-exam: links a re-exam drive (a normal examination_drives row) to its main
 * drive. main_drive_id is always the main drive, also for Re-exam 2, 3 ...
 * See the create_coe_reexams_tables migration.
 */
class Reexam extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'coe_reexams';

    protected $guarded = [];

    protected $casts = [
        'main_drive_id' => 'integer',
        'reexam_drive_id' => 'integer',
        'reexam_no' => 'integer',
        'created_at' => 'datetime',
    ];

    public function mainDrive(): BelongsTo
    {
        return $this->belongsTo(ExaminationDrive::class, 'main_drive_id');
    }

    public function reexamDrive(): BelongsTo
    {
        return $this->belongsTo(ExaminationDrive::class, 'reexam_drive_id');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(ReexamCandidate::class, 'reexam_id');
    }
}
