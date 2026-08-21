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
            'is_container' => ['nullable', 'boolean'],
        ];
    }

    /**
     * A menu points at exactly ONE destination — a Url or an Attachment — unless
     * it is explicitly a container that only holds sub-menus.
     *
     * Both: ambiguous, nothing would know which one a click follows.
     * Neither: a dead menu, EXCEPT when is_container says that is deliberate.
     *
     * The flag exists because the alternative — inferring "container" from
     * "has children" — cannot work at create time, when a menu has no children
     * yet, and would make every new parent menu impossible to save.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $isContainer = $this->boolean('is_container');
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

            if ($isContainer) {
                if ($hasRoute || $hasAttachment) {
                    $validator->errors()->add(
                        'is_container',
                        'A menu that only holds sub-menus cannot also have a Url or an Attachment.'
                    );
                }

                return;
            }

            if ($hasRoute && $hasAttachment) {
                $validator->errors()->add(
                    'attachment',
                    'Give a Url or an Attachment, not both — a menu can only point at one destination.'
                );

                return;
            }

            if (! $hasRoute && ! $hasAttachment) {
                $validator->errors()->add(
                    'route',
                    'Give a Url or an Attachment — or tick "This menu only holds sub-menus".'
                );
            }
        });
    }
}