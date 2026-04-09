<?php

######################################
// DEVELOPER INFO 
// => MANJEET CHNAD (शैतान 💀)
// => manjeetchand01@gmail.com
// => +919997294527
// => 17 Mar 2026
######################################

namespace App\Http\Requests\SidebarMenu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
    
        $menu = $this->route('menu');
        $id = is_object($menu) ? $menu->id : $menu;


        return [
            'category_id' => 'required|exists:sidebar_categories,id',
            'group_id' => 'required|exists:menu_groups,id',
            'parent_id' => 'nullable|exists:menus,id',
            'name' => 'required',
            'route' => 'nullable|string|max:255',
            'permission_name' => 'nullable|string|max:255',
            'order' => ['nullable', 'integer', Rule::unique('menus', 'order')->ignore($id)->where('group_id', $this->group_id)->where('parent_id', $this->parent_id)],
            'icon' => 'nullable|string|max:100',
            'is_active' => 'required|in:0,1',
            'target' => 'nullable|in:0,1',
        ];
    }
}