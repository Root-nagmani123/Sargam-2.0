<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\FormData;
use App\Models\FormSection;
class Form extends Model
{
    use HasFactory;

    protected $table = 'local_form';
    protected $guarded = [];
    // protected $fillable = ['name', 'description', 'visible', 'sortorder'];

    public function sections()
    {
        // return $this->hasMany(FormSection::class, 'id', 'formid');
        return $this->hasMany(FormSection::class, 'formid', 'id');
        // return [];
    }

    public function fields()
    {
        return $this->hasMany(FormData::class, 'formid', 'id');
    }
}
