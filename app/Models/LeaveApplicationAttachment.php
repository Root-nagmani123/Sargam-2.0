<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveApplicationAttachment extends Model
{
    protected $table = 'leave_application_attachment';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = [
        'leave_application_pk',
        'attachment_title',
        'file_path',
        'original_file_name',
        'created_date',
    ];

    public function application()
    {
        return $this->belongsTo(LeaveApplication::class, 'leave_application_pk', 'pk');
    }
}
