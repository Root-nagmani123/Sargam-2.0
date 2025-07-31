<?php

// app/Models/ServiceMaster.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceMaster extends Model
{
    use HasFactory;

    protected $table = 'service_master';
    protected $primaryKey = 'pk';
    protected $guarded = []; // Add other fillable fields

    public function foundationCourses()
    {
        return $this->hasMany(FoundationCourseStatus::class, 'service_master_pk', 'pk');
    }
}