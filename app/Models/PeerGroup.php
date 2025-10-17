<?php
// app/Models/PeerGroup.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PeerGroup extends Model
{
    protected $table = 'peer_groups';
    protected $fillable = ['group_name', 'is_form_active'];

    // Relationship to members
    public function members()
    {
        return $this->hasMany(PeerGroupMember::class, 'group_id');
    }

    // Static method to get groups with user info
    public static function getActiveGroupsWithUser($userId)
    {
        return self::leftJoin('peer_group_members as m', 'peer_groups.id', '=', 'm.group_id')
            ->where('peer_groups.is_form_active', 1)
            ->where('m.user_id', $userId) // filter for current user
            ->select(
                'peer_groups.id',
                'peer_groups.group_name',
                DB::raw('GROUP_CONCAT(m.course_name SEPARATOR ", ") as course_names'),
                DB::raw('GROUP_CONCAT(m.event_name SEPARATOR ", ") as event_names'),
                DB::raw('GROUP_CONCAT(m.ot_code SEPARATOR ", ") as ot_codes')
            )
            ->groupBy('peer_groups.id', 'peer_groups.group_name')
            ->get();
    }

    // Optional: groups the user belongs to
    public static function getUserGroupIds($userId)
    {
        return PeerGroupMember::where('user_id', $userId)->pluck('group_id')->toArray();
    }
}
