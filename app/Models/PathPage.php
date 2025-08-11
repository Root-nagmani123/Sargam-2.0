<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PathPageFaq;

class PathPage extends Model
{
    protected $table = 'fc_path_pages';
    protected $guarded = [];
    protected $casts = [
        'course_start_date'       => 'date',
        'course_end_date'         => 'date',
        'registration_start_date' => 'date',
        'registration_end_date'   => 'date',
        'exemption_start_date'    => 'date',
        'exemption_end_date'      => 'date',
    ];

    public function faqs()
    {
        return $this->hasMany(PathPageFaq::class);
    }
}
