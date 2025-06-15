<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PathPageFaq extends Model
{
    use HasFactory;

    protected $table = 'path_page_faqs';

    protected $fillable = [
        'path_page_id',
        'header',
        'content',
    ];

    public function page()
    {
        return $this->belongsTo(PathPage::class, 'path_page_id');
    }
}
