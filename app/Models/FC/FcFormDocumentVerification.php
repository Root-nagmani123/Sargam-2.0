<?php

namespace App\Models\FC;
use App\Models\FC\Concerns\FcUserAware;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FcFormDocumentVerification extends Model
{
    use FcUserAware;
    protected $table = 'fc_form_document_verifications';

    protected $fillable = [
        'user_id', 'username',
        'form_field_id',
        'is_verified',
        'verified_by_user_id',
        'verified_at',
        'remarks',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function studentMaster(): BelongsTo
    {
        return $this->belongsTo(StudentMaster::class, 'user_id', 'username');
    }

    public function formField(): BelongsTo
    {
        return $this->belongsTo(FcFormField::class, 'form_field_id');
    }
}
