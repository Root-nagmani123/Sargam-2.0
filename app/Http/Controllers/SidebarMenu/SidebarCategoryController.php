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
        return view('SidebarMenu.categories.index');
    }

    public function create()
    {
        return view('SidebarMenu.categories.create');
    }

    /**
     * Download / Print — one action, two formats, off the same query and the
     * same column definitions, so the CSV and the printout can't drift apart
     * (docs/new-design-index-page.md §1). ?q and ?cols are stamped on by the
     * grid so the export carries what the user is looking at.
     */
    public function export(Request $request)
    {
        $format = $request->input('format') === 'print' ? 'print' : 'csv';
        $columns = $this->service->exportColumns($request->input('cols'));
        $rows = $this->service->exportRows($request);
        $search = trim((string) $request->input('q', ''));

        if ($format === 'print') {
            return view('SidebarMenu.categories.export_print', [
                'rows' => $rows,
                'columns' => $columns,
                'search' => $search,
                'exportDate' => now()->format('d-m-Y H:i'),
            ]);
        }

        $filename = 'sidebar-categories-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($rows, $columns) {
            $handle = fopen('php://output', 'w');
            // BOM: without it Excel reads the file as ANSI and mangles any
            // non-ASCII category name.
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

    public function store(CategoryRequest $request)
    {
        $this->service->store($request->validated());
        $this->flushSidebarCaches();
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
        $this->flushSidebarCaches();
        return redirect()->back()->with('success', 'Category Updated Successfully');
    }

    public function destroy($id)
    {
        $this->service->delete($id);
        $this->flushSidebarCaches();

        return back()->with('success', 'Category Deleted Successfully');
    }

    public function status($id,Request $request)
    {
        $this->service->status($id, $request->is_active);
        $this->flushSidebarCaches();
        $status = $request->is_active == 1 ? 'Activated' : 'Deactivated';
        return response()->json([
            'success' => true,
            'message' => 'Category '.$status.' Successfully'
        ]);
    }

    /**
     * Category edits change the sidebar structure, so drop the nav/breadcrumb caches
     * (mirrors what MenuService does on menu edits) — otherwise the change stays invisible
     * until the menu_cache_ttl expires.
     */
    private function flushSidebarCaches(): void
    {
        \App\Services\SidebarMenu\SidebarNavResolver::clearCache();
        \App\Services\SidebarMenu\MenuService::clearStructureCache();
    }
    
}