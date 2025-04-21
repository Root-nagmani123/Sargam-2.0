<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseMaster extends Model
{
    protected $table = 'courses_master';
    protected $guarded = [];
    protected $primaryKey = 'pk';

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'Modified_date';

    

    public function courseCordinatorMater()
    {
        return $this->hasMany(CourseCordinatorMaster::class, 'courses_master_pk', 'pk');
    }

    
}
