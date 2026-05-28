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
use App\Services\SidebarMenu\MenuGroupService;
use App\Http\Requests\SidebarMenu\MenuGroupRequest;

class MenuGroupController extends Controller
{
    protected $service;

    public function __construct(MenuGroupService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->service->getDatatable($request);
        }
        $pageData = $this->service->pageData();
        return view('SidebarMenu.menu_groups.index', $pageData);
    }

    public function store(MenuGroupRequest $request)
    {
        $this->service->store($request->validated());
        return redirect()->back()->with('success', 'Menu Group Created Successfully');
    }

    public function show($id)
    {
        $group = $this->service->find($id);
        return view('menu_groups.show', compact('group'));
    }

    public function edit($id)
    {
        $group = $this->service->find($id);
        return view('menu_groups.edit', compact('group'));
    }

    public function update(MenuGroupRequest $request, $id)
    {
        $this->service->update($id, $request->validated());
        return redirect()->back()->with('success', 'Menu Group Updated Successfully');
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        return back()->with('success', 'Menu Group Deleted Successfully ');
    }

    public function status($id,Request $request)
    {
        $this->service->status($id, $request->is_active);
        $status = $request->is_active == 1 ? 'Activated' : 'Deactivated';
        return response()->json([
            'success' => true,
            'message' => 'Menu Group '.$status.' Successfully'
        ]);
    }
    
}