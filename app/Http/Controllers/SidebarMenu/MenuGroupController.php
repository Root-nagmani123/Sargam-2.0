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
use App\Exports\BrandedGridExport;
use App\Support\ExportCsvHeader;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

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
        $format = strtolower((string) $request->input('format', 'csv'));
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

        $columns = $this->service->exportColumns($request->input('cols'));
        $rows = $this->service->exportRows($request);

        // Two renderings of the same filters: the print sheet gets the bold HTML
        // one, the CSV / .xlsx / PDF band gets plain text. Built once, above the
        // format branch, so a filter can never be applied and then go unmentioned
        // on one of the four formats.
        $bits = [];
        $plain = [];
        $categoryId = $request->input('category_id');
        if (filled($categoryId) && ctype_digit((string) $categoryId)) {
            $category = \App\Models\SidebarMenu\SidebarCategory::find((int) $categoryId);
            if ($category) {
                $bits[] = '<strong>Category:</strong> '.e($category->name);
                $plain[] = 'Category: '.$category->name;
            }
        }
        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $bits[] = '<strong>Search:</strong> '.e($search);
            $plain[] = 'Search: '.$search;
        }

        if ($format === 'print') {
            return view('SidebarMenu.menu_groups.export_print', [
                'rows' => $rows,
                'columns' => $columns,
                'filterLine' => empty($bits) ? null : implode(' &nbsp;|&nbsp; ', $bits),
                'exportDate' => now()->format('d-m-Y H:i'),
            ]);
        }

        $exportDate = now()->format('d-m-Y h:i A');
        $filename = 'SideMenuGroups_'.now()->format('YmdHis');
        $filterLine = empty($plain) ? null : implode('  |  ', $plain);
        $reportTitle = 'SideMenu Groups';

        if ($format === 'excel') {
            return Excel::download(
                new BrandedGridExport($rows, $columns, $reportTitle, $exportDate, $filterLine),
                $filename.'.xlsx'
            );
        }

        if ($format === 'pdf') {
            return Pdf::loadView('exports.branded_grid_pdf', [
                'reportTitle' => $reportTitle,
                'columns' => $columns,
                'rows' => $rows,
                'filterLine' => $filterLine,
                'exportDate' => $exportDate,
                // Mirrors export_print.blade.php's column widths, so the PDF and
                // the printout lay out the same.
                'widths' => [
                    'sno' => '7%', 'category' => '22%', 'name' => '24%', 'icon' => '17%',
                    'order' => '9%', 'created_at' => '12%', 'status' => '9%',
                ],
            ])
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    // The page-number script in the view needs this.
                    'isPhpEnabled' => true,
                ])
                ->download($filename.'.pdf');
        }

        // The same band the .xlsx and the print/PDF headers carry, so the CSV names
        // the report and its applied filters too instead of arriving as bare columns.
        $band = ExportCsvHeader::rows(
            $reportTitle,
            $filterLine,
            $exportDate,
            is_countable($rows) ? count($rows) : null
        );

        return response()->streamDownload(function () use ($rows, $columns, $band) {
            $handle = fopen('php://output', 'w');
            // BOM: without it Excel reads the file as ANSI and mangles any
            // non-ASCII group name.
            fwrite($handle, "\xEF\xBB\xBF");
            foreach ($band as $bandRow) {
                fputcsv($handle, $bandRow);
            }
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