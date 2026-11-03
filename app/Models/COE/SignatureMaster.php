<?php

namespace App\Models\COE;

use Illuminate\Database\Eloquent\Model;

/**
 * One signature on COE → Document Management → Signature Master.
 *
 * signature_path is relative to the private `local` disk (DISK); the image is
 * only ever served through SignatureMasterController::file().
 */
class SignatureMaster extends Model
{
    public const DISK = 'local';

    public const DIR = 'coe/signatures';

    protected $table = 'coe_signature_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'designation_name',
        'person_name',
        'signature_path',
        'active_inactive',
    ];

    protected $casts = [
        'active_inactive' => 'boolean',
        'created_date' => 'datetime',
        'modified_date' => 'datetime',
    ];
}
