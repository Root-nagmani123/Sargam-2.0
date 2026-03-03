<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitType extends Model
{
    protected $table = 'estate_unit_type_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = ['unit_type'];

    /** Name for display (unit_type is the column in DB). */
    public function getNameAttribute(): string
    {
        return $this->unit_type ?? '';
    }
}
