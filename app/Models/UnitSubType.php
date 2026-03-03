<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitSubType extends Model
{
    protected $table = 'estate_unit_sub_type_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = ['unit_sub_type'];

    /** Name for display (unit_sub_type is the column in DB). */
    public function getNameAttribute(): string
    {
        return $this->unit_sub_type ?? '';
    }
}
