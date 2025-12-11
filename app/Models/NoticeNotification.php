<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoticeNotification extends Model
{
    use HasFactory;

    protected $table = "notices_notification";

    protected $fillable = [
        'notice_title',
        'description',
        'notice_type',
        'display_date',
        'expiry_date',
        'document',
        'target_audience',
        'created_by',
        'active_inactive',
        'course_master_pk'
    ];
    protected $primaryKey = 'pk';

    // Relationship with User table
    public function user()
    {
        return $this->belongsTo(\App\Models\UserCredential::class, 'created_by', 'pk');
    }
    public function course()
    {
        return $this->belongsTo(\App\Models\CourseMaster::class, 'course_master_pk', 'pk');
    }
}
