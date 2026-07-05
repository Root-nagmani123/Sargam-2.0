<?php
// ─────────────────────────────────────────────────────────────────────────────
// app/Models/OtDetail.php
// Original: ot_details table (select username,otname,otcode,... from ot_details)
// ─────────────────────────────────────────────────────────────────────────────
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtDetail extends Model
{
    protected $table = 'ot_details';

    protected $fillable = [
        'username','otname','otcode','course','c_name','gender','dob','age',
        'father_name','mobileno','blood_group','aadhar_no','abha_id',
        'house','housen','service','status',
    ];

    // ── Relations ────────────────────────────────────────────────────────────
    public function activities()
    {
        return $this->hasMany(OtActivity::class, 'username', 'username');
    }

    public function preHistory()
    {
        return $this->hasMany(PreHistory::class, 'userid', 'username');
    }

    public function pathReports()
    {
        return $this->hasMany(PathReport::class, 'userid', 'username');
    }

    public function finalFindings()
    {
        return $this->hasMany(FinalFinding::class, 'userid', 'username');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Returns the activity value for a given code, or null */
    public function activityValue(string $code): ?string
    {
        return $this->activities()
            ->where('activity', $code)
            ->value('activityval');
    }

    /** True if this OT has a pre-history record */
    public function hasPreHistory(?string $course = null): bool
    {
        $q = $this->preHistory();
        if ($course) $q->where('course', $course);
        return $q->exists();
    }

    // ── Scopes ───────────────────────────────────────────────────────────────
    public function scopeActive($query)    { return $query->where('status', 1); }
    public function scopeByCourse($query, string $course) { return $query->where('course', $course); }
}
