<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeerGroupMember extends Model
{
    protected $table = 'peer_group_members';
    
    // member_pk and user_name were missing here while both are NOT-NULL-ish real
    // columns, so any create()/firstOrCreate() silently dropped them and MySQL
    // rejected the insert. The legacy import writes via DB::table(), which is why
    // it never surfaced.
    protected $fillable = [
        'group_id',
        'member_pk',
        'user_id',
        'user_name',
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