<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;        
use App\Models\SidebarMenu\{SidebarCategory,MenuGroup,Menu};
class SidebarController extends Controller
{
    //  
    public function getGroups(Request $request)
    {
        $category = SidebarCategory::with('groups')->find($request->category_id);
       
        if(!$category) {
            return '<ul class="sidebar-groups-list"><li>No groups found.</li></ul>';
        }

        $html = '<ul class="sidebar-groups-list">';
        foreach($category->groups as $group){
                $html .= '<li class="sidebar-group-item py-2" data-id="'.$group->id.'">';
                $html .= '<a href="javascript:void(0)" class="sidebar-google-item d-flex flex-column align-items-center justify-content-center rounded-1 sidebar-group-link" data-id="'.$group->id.'" data-name="'.$group->name.'">';
                $html .= '<span class="sidebar-google-icon-wrap d-flex align-items-center justify-content-center">';
                $html .= '<i class="material-icons menu-icon material-symbols-rounded">'.$group->icon.'</i>';
                $html .= '</span>';
                $html .= '<span class="sidebar-google-label">'.$group->name.'</span>';
                $html .= '</a>';
                $html .= '</li>';
            }
        $html .= '</ul>';
        return $html;
    }

    public function getMenu(Request $request)
    {

        $group = MenuGroup::with('menus')->find($request->group_id);
        if(!$group) {
            return '<ul class="sidebar-menu-list"><li>No menus found.</li></ul>';
        }

        $html = '<ul class="sidebar-menu-list">';
        foreach($group->menus as $menu){
                $html .= '<li class="sidebar-menu-item" data-id="'.$menu->id.'">';
                $html .= '<a href="javascript:void(0)" class="sidebar-menu-link sidebar-google-item d-flex flex-column align-items-center justify-content-center rounded-3">';
                $html .= '<span class="sidebar-google-icon-wrap d-flex align-items-center justify-content-center">';
                $html .= '<i class="material-icons menu-icon material-symbols-rounded">'.$menu->icon.'</i>';
                $html .= '</span>';
                $html .= '<span class="sidebar-google-label">'.$menu->name.'</span>';
                $html .= '</a>';
                $html .= '</li>';
            }
        $html .= '</ul>';
        return $html;
    }

    public function sidebarMenus(Request $request)
    {
        $groupId = $request->group_id;
        $group = MenuGroup::with([
            'menus' => function($q){
                $q->whereNull('parent_id')
                ->where('is_active', 1)
                ->orderBy('order','ASC');
            },
            'menus.children'
        ])->find($groupId);

        if(!$group){
            return '<li>No Data Found</li>';
        }

        $html = '';

        foreach($group->menus as $menu){
            $hasChild = $menu->children->count() > 0;
            $collapseId = 'menu_'.$menu->id;

            if($hasChild){
                $html .= '
                    <li class="sidebar-item" style="background:#4077ad;
                        border-radius:30px 0 0 30px;
                        width:100%;
                        box-shadow:-2px 3px rgba(251,248,248,0.1);
                        min-width:250px;">

                        <a class="sidebar-link d-flex justify-content-between align-items-center"
                            data-bs-toggle="collapse"
                            href="#'.$collapseId.'">
                            <span class="hide-menu fw-bold">'.$menu->name.'</span>
                            <i class="material-icons">keyboard_arrow_down</i>
                        </a>
                    </li>
                    <ul class="collapse list-unstyled ps-3" id="'.$collapseId.'">';
                    foreach($menu->children as $submenu){
                        $html .= '
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="'.url($submenu->route).'" target="'.($submenu->target == 1 ? '_blank' : '_self').'">
                                <span class="hide-menu">'.$submenu->name.'</span>
                            </a>
                        </li>';
                    }
                $html .= '</ul>';
            } else {
                $html .= '
                <li class="sidebar-item">
                    <a class="sidebar-link" href="' . ($menu->route != null ? url($menu->route) : 'javascript:void(0)') . '" target="'.($menu->target == 1 ? '_blank' : '_self').'">
                        <span class="hide-menu">'.$menu->name.'</span>
                    </a>
                </li>';
            }
        }
        return $html;
    }

    public function getGroupMenus(Request $request, $group_id)
    {
        $menus = Menu::where('group_id', $group_id)->where('is_active', 1)->orderBy('order', 'ASC')->get();
        if($menus->count() > 0){
            return response()->json([
                'success' => true,
                'menus' => $menus,
                'message' => 'Menus fetched successfully'
            ]);
        }
        return response()->json([
            'success' => false,
            'menus' => [],
            'message' => 'No menus found'
        ]);
    }

    public function getCategoryGroups(Request $request, $category_id)
    {
        $groups = MenuGroup::where('category_id', $category_id)->where('is_active', 1)->orderBy('order', 'ASC')->get();
        if($groups->count() > 0){
            return response()->json([
                'success' => true,
                'groups' => $groups,
                'message' => 'Groups fetched successfully'
            ]);
        }
        return response()->json([
            'success' => false,
            'groups' => [],
            'message' => 'No groups found'
        ]);
    }
             
}
    

