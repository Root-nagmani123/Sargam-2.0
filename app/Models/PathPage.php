<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PathPageFaq;

class PathPage extends Model
{
    protected $table = 'fc_path_pages';
    protected $guarded = [];

    public function faqs()
    {
        return $this->hasMany(PathPageFaq::class);
    }
}
