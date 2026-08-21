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
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\SidebarMenu\MenuRequest;
use App\Services\SidebarMenu\MenuService;
use App\Services\SidebarMenu\MenuGroupService;
use App\Exports\BrandedGridExport;
use App\Support\ExportCsvHeader;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

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
        $format = strtolower((string) $request->input('format', 'csv'));
        abort_unless(in_array($format, ['csv', 'excel', 'pdf', 'print'], true), 404);

        $columns = $this->menuService->exportColumns($request->input('cols'));
        $rows = $this->menuService->exportRows($request);

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

        $groupId = $request->input('group_id');
        if (filled($groupId) && ctype_digit((string) $groupId)) {
            $group = \App\Models\SidebarMenu\MenuGroup::find((int) $groupId);
            if ($group) {
                $bits[] = '<strong>Group:</strong> '.e($group->name);
                $plain[] = 'Group: '.$group->name;
            }
        }

        // Mirrors MenuService::baseQuery(): "0" is the top-level-only sentinel.
        $parentId = $request->input('parent_id');
        if (filled($parentId) && ctype_digit((string) $parentId)) {
            $parentLabel = null;
            if ((int) $parentId === 0) {
                $parentLabel = 'Top level only';
            } else {
                $parent = \App\Models\SidebarMenu\Menu::find((int) $parentId);
                $parentLabel = $parent ? $parent->name : null;
            }
            if ($parentLabel !== null) {
                $bits[] = '<strong>Parent Menu:</strong> '.e($parentLabel);
                $plain[] = 'Parent Menu: '.$parentLabel;
            }
        }

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $bits[] = '<strong>Search:</strong> '.e($search);
            $plain[] = 'Search: '.$search;
        }

        if ($format === 'print') {
            return view('SidebarMenu.menus.export_print', [
                'rows' => $rows,
                'columns' => $columns,
                'filterLine' => empty($bits) ? null : implode(' &nbsp;|&nbsp; ', $bits),
                'exportDate' => now()->format('d-m-Y H:i'),
            ]);
        }

        $exportDate = now()->format('d-m-Y h:i A');
        $filename = 'Menus_'.now()->format('YmdHis');
        $filterLine = empty($plain) ? null : implode('  |  ', $plain);
        $reportTitle = 'Menus';

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
                // the printout lay out the same. Landscape below: 12 columns do
                // not fit A4 portrait.
                'widths' => [
                    'sno' => '4%', 'category' => '9%', 'group' => '11%', 'parent' => '11%',
                    'name' => '12%', 'route' => '12%', 'attachment' => '10%',
                    'permission_name' => '11%', 'icon' => '8%',
                    'order' => '4%', 'target' => '5%', 'created_at' => '7%', 'status' => '5%',
                ],
            ])
                ->setPaper('a4', 'landscape')
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
            // non-ASCII menu name.
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

    public function store(MenuRequest $request)
    {
        $data = $this->resolveAttachment($request, $request->validated(), null);
        $menu = $this->menuService->store($data);

        // Hand the name back so the grid can open filtered to the new row. With
        // 240+ menus over 25 pages, a create that lands the user on page 1 looks
        // exactly like a create that silently failed.
        return redirect()->back()
            ->with('success', 'Menu Created Successfully')
            ->with('created_menu', $menu->name);
    }

    /**
     * Fold the uploaded file into the validated data as a stored path.
     *
     * Done here rather than in MenuService so the service keeps taking a plain
     * array — Menu::create()/update() would otherwise be handed an UploadedFile
     * object and try to write it to the column. Mirrors the Useful Links upload
     * (public disk, replace deletes the old file) so the two behave the same.
     *
     * @param  \App\Models\SidebarMenu\Menu|null  $existing
     */
    private function resolveAttachment(MenuRequest $request, array $data, $existing): array
    {
        $current = $existing->attachment ?? null;
        $remove = (bool) ($data['remove_attachment'] ?? false);

        // Never a column of its own — it only drives the branches below.
        unset($data['remove_attachment']);

        if ($request->hasFile('attachment')) {
            // Replacing: drop the old file rather than orphaning it on disk.
            if ($current && Storage::disk('public')->exists($current)) {
                Storage::disk('public')->delete($current);
            }
            $data['attachment'] = $this->storeAttachment($request->file('attachment'));

            return $data;
        }

        if ($remove) {
            if ($current && Storage::disk('public')->exists($current)) {
                Storage::disk('public')->delete($current);
            }
            $data['attachment'] = null;

            return $data;
        }

        // No file posted and no removal asked for: leave whatever is already
        // stored alone. Unsetting the key is what stops an edit that did not
        // touch the field from blanking the column.
        unset($data['attachment']);

        return $data;
    }

    /**
     * Store the upload under a readable name.
     *
     * store() would name it with a 40-character hash, which is what the grid and
     * every export would then display. Keep the original name, slugged, with a
     * short random suffix so two uploads of "report.pdf" cannot collide.
     *
     * The extension is taken from the file's CONTENT, never from the uploaded
     * name. `mimes:` validates guessExtension(), so anything reaching here already
     * guesses to one of the allowed types — but naming the file from the *client*
     * extension would still let a genuine PNG called "payload.html" land as .html
     * on the public disk, where the browser reads it back as markup rather than as
     * an image. Deriving it here keeps the stored name and the validated type the
     * same fact. Do not reintroduce getClientOriginalExtension() here.
     */
    private function storeAttachment($file): string
    {
        $extension = strtolower((string) $file->guessExtension() ?: 'dat');
        $base = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = \Illuminate\Support\Str::slug($base) ?: 'attachment';

        $name = \Illuminate\Support\Str::limit($slug, 60, '').'-'.\Illuminate\Support\Str::random(6).'.'.$extension;

        return $file->storeAs('menu-attachments', $name, 'public');
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
        $data = $this->resolveAttachment($request, $request->validated(), $this->menuService->find($id));
        $this->menuService->update($id, $data);
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