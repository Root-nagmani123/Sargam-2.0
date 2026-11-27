<?php

namespace Modules\GuestTaClaim\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimHistory extends Model
{
    protected $table = 'gtc_claim_histories';

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function claim()
    {
        return $this->belongsTo(Claim::class);
    }
}
