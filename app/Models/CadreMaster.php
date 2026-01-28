<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CadreMaster extends Model
{
    protected $table = "cadre_master";
    protected $primaryKey = 'pk';
    public $timestamps = false;
    protected $guarded = [];

    /**
     * Get all students belonging to this cadre
     */
    public function students()
    {
        return $this->hasMany(StudentMaster::class, 'cadre_master_pk', 'pk');
    }
}
