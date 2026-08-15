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

    /**
     * Download / Print — one action, two formats, off the same query and the
     * same column definitions, so the CSV and the printout can't drift apart
     * (docs/new-design-index-page.md §1). ?category_id, ?q and ?cols are stamped
     * on by the grid so the export carries what the user is looking at.
     */
    public function export(Request $request)
    {
        $format = $request->input('format') === 'print' ? 'print' : 'csv';
        $columns = $this->service->exportColumns($request->input('cols'));
        $rows = $this->service->exportRows($request);

        if ($format === 'print') {
            $bits = [];
            $categoryId = $request->input('category_id');
            if (filled($categoryId) && ctype_digit((string) $categoryId)) {
                $category = \App\Models\SidebarMenu\SidebarCategory::find((int) $categoryId);
                if ($category) {
                    $bits[] = '<strong>Category:</strong> '.e($category->name);
                }
            }
            $search = trim((string) $request->input('q', ''));
            if ($search !== '') {
                $bits[] = '<strong>Search:</strong> '.e($search);
            }

            return view('SidebarMenu.menu_groups.export_print', [
                'rows' => $rows,
                'columns' => $columns,
                'filterLine' => empty($bits) ? null : implode(' &nbsp;|&nbsp; ', $bits),
                'exportDate' => now()->format('d-m-Y H:i'),
            ]);
        }

        $filename = 'sidebar-menu-groups-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($rows, $columns) {
            $handle = fopen('php://output', 'w');
            // BOM: without it Excel reads the file as ANSI and mangles any
            // non-ASCII group name.
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

    public function store(MenuGroupRequest $request)
    {
        $this->service->store($request->validated());
        $this->flushSidebarCaches();
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
        $this->flushSidebarCaches();
        return redirect()->back()->with('success', 'Menu Group Updated Successfully');
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        $this->flushSidebarCaches();
        return back()->with('success', 'Menu Group Deleted Successfully ');
    }

    public function status($id,Request $request)
    {
        $this->service->status($id, $request->is_active);
        $this->flushSidebarCaches();
        $status = $request->is_active == 1 ? 'Deactivated' : 'Activated';
        return response()->json([
            'success' => true,
            'message' => 'Menu Group '.$status.' Successfully'
        ]);
    }

    /**
     * Menu group edits change the sidebar structure, so drop the nav/breadcrumb caches
     * (mirrors what MenuService does on menu edits) — otherwise the change stays invisible
     * until the menu_cache_ttl expires.
     */
    private function flushSidebarCaches(): void
    {
        \App\Services\SidebarMenu\SidebarNavResolver::clearCache();
        \App\Services\SidebarMenu\MenuService::clearStructureCache();
    }
    
}