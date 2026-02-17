<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstateCampus extends Model
{
    protected $table = 'estate_campus_master';

    protected $primaryKey = 'pk';

    public $timestamps = false;

    protected $fillable = ['campus_name', 'description'];

    /** Name for display (campus_name is the column in DB). */
    public function getNameAttribute(): string
    {
        return $this->campus_name ?? '';
    }
}
