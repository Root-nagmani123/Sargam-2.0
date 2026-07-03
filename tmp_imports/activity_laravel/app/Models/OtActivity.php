<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Converted from: otactivity_details table
 * Original PHP: upload.php, updatedata.php, deleterecord.php
 *
 * Activity codes used (from original source):
 *   joined, idcard, biometric, trgind, height, weight, spo2,
 *   pulse, bp, souvenir, preremarks, vialtube, bloodsample
 */
class OtActivity extends Model
{
    protected $table = 'otactivity_details';

    protected $fillable = [
        'activityid','username','activity','activityval',
        'activitydt','submitedby','course','status',
    ];

    // ── Department → activity code map (from showotjoined.php + showreportall.php) ──
    public const DEPT_ACTIVITY = [
        'admin'    => 'joined',
        'security' => 'idcard',
        'it'       => 'biometric',
        'trg'      => 'trgind',
        'medical'  => 'height',
        'shop'     => 'souvenir',
    ];

    // ── All tracked activity codes ────────────────────────────────────────────
    public const ALL_ACTIVITIES = [
        'joined','idcard','biometric','trgind',
        'height','weight','spo2','pulse','bp',
        'souvenir','preremarks','vialtube','bloodsample',
    ];

    // ── Relations ────────────────────────────────────────────────────────────
    public function ot()
    {
        return $this->belongsTo(OtDetail::class, 'username', 'username');
    }

    public function activityMaster()
    {
        return $this->belongsTo(ActivityMaster::class, 'activity', 'menuid');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────
    public function scopeActive($query)             { return $query->where('status', 1); }
    public function scopeForActivity($q, $code)    { return $q->where('activity', $code); }
    public function scopeByUser($q, $username)     { return $q->where('username', $username); }
    public function scopeByStaff($q, $staff)       { return $q->where('submitedby', $staff); }
}
