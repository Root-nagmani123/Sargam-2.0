<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PathPageFaq;

class PathPage extends Model
{
    protected $fillable = ['register_course', 'apply_exemption', 'already_registered'];

    public function faqs()
    {
        return $this->hasMany(PathPageFaq::class);
    }
}
