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

class Menu extends Model
{
    use HasFactory,SoftDeletes;

    protected $guarded = [];

    public function group()
    {
        return $this->belongsTo(MenuGroup::class, 'group_id');
    }
    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')
            ->where('is_active', 1)
            ->orderBy('order')
            ->with('children');
    }

    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }
}