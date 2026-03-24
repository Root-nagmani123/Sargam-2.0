<?php

namespace App\Http\Requests\SidebarMenu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class CategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }



    public function rules()
    {
        $category = $this->route('category');
        $id = is_object($category) ? $category->id : $category;

        return [
            'name' => [
                'required',
                'max:100',
                Rule::unique('sidebar_categories', 'name')->ignore($id),
            ],

            'slug' => [
                'required',
                'max:100',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('sidebar_categories', 'slug')->ignore($id),
            ],

            'icon' => ['nullable','max:100'],

            'order' => ['nullable','integer'],

            'is_active' => ['required','in:0,1'],
        ];
    }
}
