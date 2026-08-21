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
use App\Exports\BrandedGridExport;
use App\Support\ExportCsvHeader;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
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
     * Download / Print — one action, four formats (csv, excel, pdf, print), off
     * the same query and the same column definitions, so a spreadsheet, a PDF and
     * a printout can't drift apart (docs/new-design-index-page.md §1). ?q and
     * ?cols are stamped on by the grid so every format carries what the user is
     * looking at.
     */
    public function export(Request $request)
    {
        $format = strtolower((string) $request->input('format', 'csv'));
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

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

        $exportDate = now()->format('d-m-Y h:i A');
        $filename = 'TopbarCategory_'.now()->format('YmdHis');
        $filterLine = $search !== '' ? 'Search: '.$search : null;
        $reportTitle = 'Topbar Category';

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
                    'sno' => '7%', 'name' => '24%', 'slug' => '22%', 'icon' => '17%',
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
            // non-ASCII category name.
            fwrite($handle, "\xEF\xBB\xBF");
            foreach ($band as $bandRow) {
                fputcsv($handle, $bandRow);
            }
            fputcsv($handle, array_column($columns, 'heading'));

            $index = 0;
            foreach ($rows as $row) {
                fputcsv($handle, array_map(
                    fn (array $col) => (string) $col['value']($row, $index),
                    $columns
                ));
                $index++;
            }

            fclose($handle);
        }, $filename.'.csv', [
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