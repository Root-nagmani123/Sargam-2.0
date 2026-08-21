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
            'permission_name' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('menus', 'permission_name')
                    ->ignore($id)
                    ->where(function ($query) {
                        $query->where('group_id', $this->group_id);

                        if ($this->parent_id) {
                            $query->where('parent_id', $this->parent_id);
                        } else {
                            $query->whereNull('parent_id');
                        }
                    }),
            ],
            'order' => [
                'nullable',
                'integer',
                Rule::unique('menus', 'order')
                    ->ignore($id)
                    ->where(function ($query) {
                        $query->where('group_id', $this->group_id);

                        if ($this->parent_id) {
                            $query->where('parent_id', $this->parent_id);
                        } else {
                            $query->whereNull('parent_id');
                        }
                    }),
            ],

            'icon' => 'nullable|string|max:100',
            'is_active' => 'required|in:0,1',
            'target' => 'nullable|in:0,1',

            // Attachment: the same types and 10 MB ceiling Useful Links accepts,
            // so the two upload fields behave identically. `remove_attachment` is
            // the Edit modal's "delete the current file" checkbox.
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,ppt,pptx', 'max:10240'],
            'remove_attachment' => ['nullable', 'boolean'],
        ];
    }

    /**
     * A menu points at ONE destination: a URL or an attachment, never both.
     *
     * Giving both leaves it ambiguous which one a click should follow, and no
     * existing menu does it (0 of 243), so rejecting it costs nothing.
     *
     * Deliberately NOT requiring one of the two: 7 live menus have neither, 4 of
     * them because they are containers that only hold sub-menus — exactly what
     * the Url field's own help text tells you to do. Requiring a destination
     * would make those un-editable and block creating a new parent menu.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $hasRoute = filled($this->input('route'));

            // On edit, a file already on the record counts — unless this submit is
            // removing it.
            $keepsExisting = false;
            $menu = $this->route('menu');
            $id = is_object($menu) ? $menu->id : $menu;
            if ($id && ! $this->boolean('remove_attachment')) {
                $keepsExisting = filled(
                    optional(\App\Models\SidebarMenu\Menu::find($id))->attachment
                );
            }

            $hasAttachment = $this->hasFile('attachment') || $keepsExisting;

            if ($hasRoute && $hasAttachment) {
                $validator->errors()->add(
                    'attachment',
                    'Give a Url or an Attachment, not both — a menu can only point at one destination.'
                );
            }
        });
    }
}