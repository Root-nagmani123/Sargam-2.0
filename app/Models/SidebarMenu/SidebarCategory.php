<?php

######################################
// DEVELOPER INFO 
// => MANJEET CHNAD (शैतान 💀)
// => manjeetchand01@gmail.com
// => +919997294527
// => 17 Mar 2026
######################################

namespace App\Models\SidebarMenu;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class SidebarCategory extends Model
{
    use HasFactory,SoftDeletes;
    protected $guarded = [];

    public function groups()
    {
        return $this->hasMany(MenuGroup::class, 'category_id')->orderBy('order','ASC')->where('is_active',1);
    }
}
