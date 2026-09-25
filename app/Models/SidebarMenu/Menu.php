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

    // Explicit, not $guarded = []: these rows define permission names, so a column
    // added to menus later must not become writable from the edit form by default.
    // This is exactly what MenuRequest validates plus permission_name, which
    // MenuService derives from the name rather than taking from the form.
    protected $fillable = [
        'category_id',
        'group_id',
        'parent_id',
        'name',
        'route',
        'permission_name',
        'order',
        'icon',
        'is_active',
        'target',
    ];

    public function category()
    {
        return $this->belongsTo(SidebarCategory::class, 'category_id');
    }

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