<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One evaluator's free-text remark about one evaluated OT.
 *
 * Keyed (group_id, member_id, evaluator_id) - see
 * 2026_08_25_000001_create_peer_evaluation_remarks_table for why this is its own
 * table rather than a column on peer_scores or a reuse of reflection_responses.
 */
class PeerEvaluationRemark extends Model
{
    protected $table = 'peer_evaluation_remarks';

    protected $fillable = [
        'group_id',
        'member_id',
        'evaluator_id',
        'remarks',
    ];

    protected $casts = [
        'group_id' => 'integer',
        'member_id' => 'integer',
        'evaluator_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** The evaluated OT. */
    public function member(): BelongsTo
    {
        return $this->belongsTo(PeerGroupMember::class, 'member_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(PeerGroup::class, 'group_id');
    }

    /** The person who wrote it. user_credentials keys on `pk`. */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id', 'pk');
    }
}
