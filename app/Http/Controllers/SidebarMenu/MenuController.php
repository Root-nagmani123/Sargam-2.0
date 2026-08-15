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

    /**
     * Download / Print — one action, two formats, off the same query and the
     * same column definitions, so the CSV and the printout can't drift apart
     * (docs/new-design-index-page.md §1). ?category_id, ?group_id, ?q and ?cols
     * are stamped on by the grid so the export carries what the user sees.
     */
    public function export(Request $request)
    {
        $format = $request->input('format') === 'print' ? 'print' : 'csv';
        $columns = $this->menuService->exportColumns($request->input('cols'));
        $rows = $this->menuService->exportRows($request);

        if ($format === 'print') {
            $bits = [];

            $categoryId = $request->input('category_id');
            if (filled($categoryId) && ctype_digit((string) $categoryId)) {
                $category = \App\Models\SidebarMenu\SidebarCategory::find((int) $categoryId);
                if ($category) {
                    $bits[] = '<strong>Category:</strong> '.e($category->name);
                }
            }

            $groupId = $request->input('group_id');
            if (filled($groupId) && ctype_digit((string) $groupId)) {
                $group = \App\Models\SidebarMenu\MenuGroup::find((int) $groupId);
                if ($group) {
                    $bits[] = '<strong>Group:</strong> '.e($group->name);
                }
            }

            $search = trim((string) $request->input('q', ''));
            if ($search !== '') {
                $bits[] = '<strong>Search:</strong> '.e($search);
            }

            return view('SidebarMenu.menus.export_print', [
                'rows' => $rows,
                'columns' => $columns,
                'filterLine' => empty($bits) ? null : implode(' &nbsp;|&nbsp; ', $bits),
                'exportDate' => now()->format('d-m-Y H:i'),
            ]);
        }

        $filename = 'sidebar-menus-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($rows, $columns) {
            $handle = fopen('php://output', 'w');
            // BOM: without it Excel reads the file as ANSI and mangles any
            // non-ASCII menu name.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, array_column($columns, 'heading'));

            foreach ($rows as $index => $row) {
                fputcsv($handle, array_map(
                    fn (array $col) => (string) $col['value']($row, $index),
                    $columns
                ));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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