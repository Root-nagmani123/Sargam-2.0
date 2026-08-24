<?php

######################################
// DEVELOPER INFO
// => MANJEET CHNAD (शैतान 💀)
// => manjeetchand01@gmail.com
// => +919997294527
// => 17 Mar 2026
######################################

namespace App\Services\SidebarMenu;
use App\Models\SidebarMenu\MenuGroup;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use App\Models\SidebarMenu\SidebarCategory;

class MenuGroupService
{
    public function pageData(): array
    {
        return [
            'categories' => $this->activeCategories(),
            'materialIcons' => $this->materialSymbolsRoundedIconNames(),
        ];
    }

    public function activeCategories()
    {
        return SidebarCategory::where('is_active', 1)->orderBy('order', 'asc')->get();
    }

    /**
     * Material Symbols Rounded ligature names from official codepoints (resources/data/material-symbols-rounded.codepoints).
     * Stored DB values are the icon name only (e.g. "home"), used as text in &lt;i class="material-icons material-symbols-rounded"&gt;.
     *
     * ⚠️ ~4,200 names. Never render these as <option> elements — see the note on
     * the icon picker in the view. Ship the array once as JSON and let Choices.js
     * build its own list from it.
     */
    public function materialSymbolsRoundedIconNames(): array
    {
        $path = resource_path('data/material-symbols-rounded.codepoints');
        if (! is_readable($path)) {
            return [];
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }
        $names = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = preg_split('/\s+/', $line, 2);
            $name = $parts[0] ?? '';
            if ($name !== '') {
                $names[] = $name;
            }
        }
        sort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return $names;
    }

    public function getAll()
    {
        return MenuGroup::latest()->get();
    }

    public function store(array $data)
    {
        $data['order'] = $data['order'] ?? MenuGroup::max('order') + 1;
        return MenuGroup::create($data);
    }

    public function status($id, $status)
    {
        $group = $this->find($id);
        $group->update(['is_active' => $status]);
        return $group;
    }

    public function find($id)
    {
        return MenuGroup::findOrFail($id);
    }

    public function update($id, array $data)
    {
        $group = $this->find($id);
        $data['order'] = $data['order'] ?? MenuGroup::max('order') + 1;
        return $group->update($data);
    }

    public function delete($id)
    {
        $group = $this->find($id);
        return $group->delete();
    }

    /**
     * @ Base Query
     *
     * Deliberately UNORDERED. Yajra appends its `order[]` clauses to whatever
     * the query already carries, so an ORDER BY here would stay the primary
     * sort and every header click would silently do nothing. The grid asks for
     * `order` ascending by default (its `order:` option); exportRows() adds the
     * same default for the downloads.
     *
     * ⚠️ `select('menu_groups.*')` is load-bearing. Sorting the grid by Category
     * makes Yajra leftJoin `sidebar_categories`, and with a bare `select *` that
     * table's `name`/`icon`/`order` columns overwrite this one's in every row —
     * the Name column silently starts showing the CATEGORY name. Qualifying the
     * select keeps the join to ordering only.
     */
    protected function baseQuery(Request $request)
    {
        $query = MenuGroup::query()->select('menu_groups.*')->with('category');

        $categoryId = $request->input('category_id');
        if (filled($categoryId) && ctype_digit((string) $categoryId)) {
            $query->where('category_id', (int) $categoryId);
        }

        return $query;
    }

    public function getDatatable(Request $request)
    {
        return DataTables::of($this->baseQuery($request))
            // editColumn (not addColumn) on the real DB columns: Yajra then still
            // treats them as sortable/searchable SQL columns and only swaps the
            // rendered value.
            ->addColumn('category_name', fn ($e) =>
                optional($e->category)->name ?: '<span class="sbm-muted">—</span>'
            )
            ->editColumn('created_at', fn ($e) =>
                optional($e)->created_at ? optional($e)->created_at->format('d-m-Y') : '-'
            )
            ->editColumn('order', fn ($e) =>
                $e->order === null ? '<span class="sbm-muted">—</span>' : $this->orderBadge($e)
            )
            ->editColumn('icon', fn ($e) => $this->iconBadge($e))
            ->addColumn('status', fn ($e) => $this->statusBadge($e))
            ->addColumn('action', fn ($e) => $this->actionButtons($e))
            ->rawColumns(['action', 'status', 'icon', 'order', 'category_name'])
            ->addIndexColumn()
            ->make(true);
    }

    private function orderBadge($data)
    {
        return '<span class="sbm-order">'.e($data->order).'</span>';
    }

    private function iconBadge($data)
    {
        if ($data->icon === null || trim((string) $data->icon) === '') {
            return '<span class="sbm-muted">—</span>';
        }

        $icon = trim($data->icon);
        if (str_contains($icon, 'bi-') || str_starts_with($icon, 'bi ')) {
            $iconClass = str_contains($icon, 'bi ') ? $icon : 'bi '.$icon;

            return '<span class="sbm-icon-chip" title="'.e($icon).'">'
                .'<i class="'.e($iconClass).'" aria-hidden="true"></i></span>';
        }

        return '<span class="sbm-icon-chip" title="'.e($icon).'">'
            .'<span class="material-icons material-symbols-rounded" aria-hidden="true">'.e($icon).'</span></span>';
    }

    /**
     * Status column — a soft badge, display only. The control that changes it is
     * the switch in the Action column (§3b).
     */
    private function statusBadge($data)
    {
        $isActive = (int) $data->is_active === 1;

        return '<span class="status-pill badge rounded-1 '
            .($isActive ? 'bg-success-subtle' : 'bg-danger-subtle').'">'
            .($isActive ? 'Active' : 'Inactive')
            .'</span>';
    }

    /**
     * Action column — Edit · status switch · Delete, three equal-width stacks of
     * an icon over a caption (§3b).
     *
     * Delete is guarded: an ACTIVE group still drives the live sidebar, so it
     * renders as a disabled span explaining why rather than a red icon the page
     * would refuse. The switch caption names the ACTION, not the state.
     */
    private function actionButtons($data)
    {
        $isActive = (int) $data->is_active === 1;
        $name = e($data->name);
        $switchId = 'sbmGroupStatus'.(int) $data->id;

        $html = '<div class="sbm-act-group" role="group" aria-label="Actions for '.$name.'">';

        $html .= '
            <button type="button" class="sbm-act sbm-act--edit sbm-edit-btn"
                    data-id="'.(int) $data->id.'"
                    data-name="'.$name.'"
                    data-category="'.(int) $data->category_id.'"
                    data-icon="'.e((string) $data->icon).'"
                    data-order="'.e((string) $data->order).'"
                    data-status="'.($isActive ? '1' : '0').'"
                    title="Edit menu group" aria-label="Edit menu group '.$name.'">
                <span class="sbm-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                <span class="sbm-act__label">Edit</span>
            </button>';

        // No .form-check/.form-switch wrapper — that combination yanks the input
        // -2.375rem left (custom.css:107-112) and breaks this layout (§3b trap 1).
        //
        // Two classes, two jobs:
        //   plain-status-toggle              — the design-system TOGGLE skin
        //     (custom.css:41-95). It is the wrapper-less variant, which is exactly
        //     this layout. Drop it and the input falls back to Bootstrap's square
        //     checkbox.
        //   sidebar-menu-group-status-toggle — this page's JS hook. Deliberately
        //     NOT `status-toggle`: that one is bound globally by custom.js:170 to
        //     the generic table/column endpoint, and this grid posts to its own
        //     route instead.
        $html .= '
            <label class="sbm-act sbm-act--toggle" for="'.$switchId.'">
                <span class="sbm-act__icon">
                    <input class="form-check-input plain-status-toggle sidebar-menu-group-status-toggle"
                           type="checkbox" role="switch"
                           id="'.$switchId.'"
                           data-table="menu_groups" data-column="is_active"
                           data-id="'.(int) $data->id.'" data-name="'.$name.'"
                           '.($isActive ? 'checked' : '').'
                           aria-label="'.($isActive ? 'Deactivate' : 'Activate').' menu group '.$name.'">
                </span>
                <span class="sbm-act__label">'.($isActive ? 'Deactivate' : 'Activate').'</span>
            </label>';

        if ($isActive) {
            $html .= '
            <span class="sbm-act sbm-act--del is-disabled"
                  title="Deactivate this menu group before deleting it" aria-disabled="true">
                <span class="sbm-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
                <span class="sbm-act__label">Delete</span>
            </span>';
        } else {
            $html .= '
            <form action="'.route('sidebar.menu-groups.destroy', $data->id).'" method="POST" class="sbm-delete-form">
                '.csrf_field().'
                '.method_field('DELETE').'
                <button type="submit" class="sbm-act sbm-act--del" data-name="'.$name.'"
                        aria-label="Delete menu group '.$name.'">
                    <span class="sbm-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
                    <span class="sbm-act__label">Delete</span>
                </button>
            </form>';
        }

        return $html.'</div>';
    }

    /* ────────────────────────────────────────────────────────────────────────
       Exports
       One definition feeds BOTH the CSV and the print view, so the two cannot
       drift apart (docs/new-design-index-page.md §1). `key` is what the grid's
       Columns modal sends back as ?cols=; keep the first column identical to
       the grid's.
       ──────────────────────────────────────────────────────────────────────── */

    public function exportColumnDefs(): array
    {
        return [
            ['key' => 'sno', 'heading' => 'Sr No.', 'class' => 'sbm-print-sno',
                'value' => fn ($row, $index) => $index + 1],
            ['key' => 'category', 'heading' => 'Category', 'class' => 'sbm-print-category',
                'value' => fn ($row) => optional($row->category)->name ?: '-'],
            ['key' => 'name', 'heading' => 'Name', 'class' => 'sbm-print-name',
                'value' => fn ($row) => (string) $row->name],
            ['key' => 'icon', 'heading' => 'Icon', 'class' => 'sbm-print-icon',
                'value' => fn ($row) => filled($row->icon) ? (string) $row->icon : '-'],
            ['key' => 'order', 'heading' => 'Order', 'class' => 'sbm-print-order',
                'value' => fn ($row) => $row->order === null ? '-' : (string) $row->order],
            ['key' => 'created_at', 'heading' => 'Created', 'class' => 'sbm-print-created',
                'value' => fn ($row) => $row->created_at ? $row->created_at->format('d-m-Y') : '-'],
            ['key' => 'status', 'heading' => 'Status', 'class' => 'sbm-print-status',
                'value' => fn ($row) => (int) $row->is_active === 1 ? 'Active' : 'Inactive'],
        ];
    }

    /**
     * Columns still ticked in the grid's Columns modal, in table order. No
     * ?cols= at all means "every column"; an unknown key is ignored.
     */
    public function exportColumns(?string $cols): array
    {
        $defs = $this->exportColumnDefs();

        if (! filled($cols)) {
            return $defs;
        }

        $wanted = array_filter(array_map('trim', explode(',', $cols)));
        if (empty($wanted)) {
            return $defs;
        }

        return array_values(array_filter(
            $defs,
            fn (array $def) => in_array($def['key'], $wanted, true)
        ));
    }

    /**
     * The rows the export writes — the same category filter and search term the
     * grid is showing, so a download matches what is on screen.
     */
    public function exportRows(Request $request)
    {
        $query = $this->baseQuery($request)->orderBy('order', 'asc');

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where('name', 'like', $like)
                    ->orWhere('icon', 'like', $like)
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like));
            });
        }

        return $query->get();
    }
}
