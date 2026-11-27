<?php

namespace Modules\GuestTaClaim\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimAttachment extends Model
{
    protected $table = 'gtc_claim_attachments';

    protected $guarded = ['id'];

    public function claim()
    {
        return $this->belongsTo(Claim::class);
    }
}
