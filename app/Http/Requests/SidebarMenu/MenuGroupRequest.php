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

class MenuGroupRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $menuGroup = $this->route('menu-group');
        $id = is_object($menuGroup) ? $menuGroup->id : $menuGroup;

        return [
            'category_id' => 'required|exists:sidebar_categories,id',
            'name' => [
                'required',
                'max:100',
                Rule::unique('menu_groups', 'name')->ignore($id),
            ],
            'icon' => ['nullable','max:100'],
            'order' => ['nullable','integer'],
            'is_active' => ['required','in:0,1'],
        ];
    }
}