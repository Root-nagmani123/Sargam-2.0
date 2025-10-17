<?php

// app/Models/PeerGroupMember.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeerGroupMember extends Model
{
    protected $table = 'peer_group_members';
    protected $fillable = ['group_id', 'user_id', 'course_name', 'event_name', 'ot_code'];

    public function group()
    {
        return $this->belongsTo(PeerGroup::class, 'group_id');
    }
}
