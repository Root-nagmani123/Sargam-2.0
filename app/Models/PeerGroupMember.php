<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeerGroupMember extends Model
{
    protected $table = 'peer_group_members';
    
    protected $fillable = [
        'group_id', 
        'user_id', 
        'course_name', 
        'event_name', 
        'ot_code',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(PeerGroup::class, 'group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}