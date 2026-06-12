<?php

######################################
// DEVELOPER INFO 
// => MANJEET CHNAD (शैतान 💀)
// => manjeetchand01@gmail.com
// => +919997294527
// => 17 Mar 2026
######################################

namespace App\Http\Controllers\SidebarMenu;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\SidebarMenu\MenuRequest;
use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\MenuGroupService;

class MenuController extends Controller
{
    protected $menuService;
    protected $groupService;

    public function __construct(MenuService $menuService, MenuGroupService $groupService)
    {
        $this->menuService = $menuService;
        $this->groupService = $groupService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->menuService->getDatatable($request);
        }
        $pageData = $this->menuService->pageData();
        return view('SidebarMenu.menus.index', $pageData);
    }

    public function create()
    {
        $groups = $this->groupService->getAll();
        $menus = $this->menuService->getForDropdown();

        return view('menus.create', compact('groups', 'menus'));
    }

    public function store(MenuRequest $request)
    {
        $this->menuService->store($request->validated());
        return redirect()->back()->with('success', 'Menu Created Successfully');
    }

    public function edit($id)
    {
        $menu = $this->menuService->find($id);
        $groups = $this->groupService->getAll();
        $menus = $this->menuService->getForDropdown();

        return view('menus.edit', compact('menu', 'groups', 'menus'));
    }

    public function update(MenuRequest $request, $id)
    {
        $this->menuService->update($id, $request->validated());
        return redirect()->back()->with('success', 'Menu Updated Successfully');
    }

    public function destroy($id)
    {
        $this->menuService->delete($id);

        return back();
    }

    public function status($id,Request $request)
    {
        $this->menuService->status($id, $request->is_active);
        $status = $request->is_active == 1 ? 'Activated' : 'Deactivated';
        return response()->json([
            'success' => true,
            'message' => 'Menu '.$status.' Successfully'
        ]);
    }
}