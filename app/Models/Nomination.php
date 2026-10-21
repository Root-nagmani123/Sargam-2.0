<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nomination extends Model
{
    protected $table = 'nomination';
    protected $primaryKey = 'pk';
    public $timestamps = false;
    // Status, publish and active flags are deliberately not mass-assignable:
    // they are set explicitly by the controller actions that own them (PR #340 F-010).
    protected $fillable = [
        'nomination_drive_pk',
        'club_society_master_pk',
        'club_society_role_master_pk',
        'nominee_student_pk',
        'nominated_by_student_pk',
    ];

    public const STATUS_PENDING   = 'pending';
    public const STATUS_ACCEPTED  = 'accepted';
    public const STATUS_WITHDRAWN = 'withdrawn';

    /**
     * Display status for a group of nomination rows (one per nominator) by an
     * explicit precedence instead of MAX() over the string, which ranked
     * 'withdrawn' > 'pending' > 'accepted' alphabetically (PR #340 F-003):
     * any withdrawn row -> withdrawn, else any accepted -> accepted, else pending.
     */
    public static function statusPrecedenceSql(string $column = 'status'): string
    {
        return "CASE"
            . " WHEN SUM(CASE WHEN {$column} = '" . self::STATUS_WITHDRAWN . "' THEN 1 ELSE 0 END) > 0 THEN '" . self::STATUS_WITHDRAWN . "'"
            . " WHEN SUM(CASE WHEN {$column} = '" . self::STATUS_ACCEPTED . "' THEN 1 ELSE 0 END) > 0 THEN '" . self::STATUS_ACCEPTED . "'"
            . " ELSE '" . self::STATUS_PENDING . "' END";
    }

    public function drive()
    {
        return $this->belongsTo(NominationDrive::class, 'nomination_drive_pk', 'pk');
    }

    public function clubSociety()
    {
        return $this->belongsTo(ClubSocietyMaster::class, 'club_society_master_pk', 'pk');
    }

    public function role()
    {
        return $this->belongsTo(ClubSocietyRoleMaster::class, 'club_society_role_master_pk', 'pk');
    }
}
