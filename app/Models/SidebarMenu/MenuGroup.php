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


class MenuGroup extends Model
{
    use HasFactory,SoftDeletes;

    protected $guarded = [];
    
    public function category()
    {
        return $this->belongsTo(SidebarCategory::class, 'category_id');
    }

    public function menus()
    {
        return $this->hasMany(Menu::class,'group_id')
            ->whereNull('parent_id')
            ->where('is_active', 1)
            ->orderBy('order')
            ->with('children');
    }
}
