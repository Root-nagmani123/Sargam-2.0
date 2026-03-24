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
use App\Services\SidebarMenu\SidebarCategoryService;
use App\Http\Requests\SidebarMenu\CategoryRequest;
use Illuminate\Http\Request;


class SidebarCategoryController extends Controller
{
    protected $service;

    public function __construct(SidebarCategoryService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->service->getDatatable($request);
        }
        $pageData = $this->service->pageData();
        return view('SidebarMenu.categories.index',$pageData);
    }

    public function create()
    {
        return view('SidebarMenu.categories.create');
    }

    public function store(CategoryRequest $request)
    {
        $this->service->store($request->validated());
        return redirect()->back()->with('success', 'Category Created Successfully');
    }

    public function edit($id)
    {
        $category = $this->service->find($id);

        return view('SidebarMenu.categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, $id)
    {
        $this->service->update($id, $request->validated());
        return redirect()->back()->with('success', 'Category Updated Successfully');
    }

    public function destroy($id)
    {
        $this->service->delete($id);

        return back()->with('success', 'Category Deleted Successfully');
    }

    public function status($id,Request $request)
    {
        $this->service->status($id, $request->is_active);
        $status = $request->is_active == 1 ? 'Activated' : 'Deactivated';
        return response()->json([
            'success' => true,
            'message' => 'Category '.$status.' Successfully'
        ]);
    }
    
}