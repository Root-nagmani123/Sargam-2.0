<?php

######################################
// DEVELOPER INFO
// => MANJEET CHNAD (शैतान 💀)
// => manjeetchand01@gmail.com
// => +919997294527
// => 17 Mar 2026
######################################
namespace App\Services\SidebarMenu;
use Yajra\DataTables\Facades\DataTables;
use App\Models\SidebarMenu\SidebarCategory;
use App\Services\SidebarMenu\SidebarNavResolver;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class SidebarCategoryService
{
    public function getAll()
    {
        return SidebarCategory::orderBy('order')->get();
    }

    public function getActive()
    {
        return SidebarCategory::where('is_active', 1)
            ->orderBy('order')
            ->get();
    }

    public function status($id, $status)
    {
        $category = $this->find($id);
        $category->update(['is_active' => $status]);
        SidebarNavResolver::clearCache();
        return $category;
    }

    public function store(array $data)
    {
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['order'] = $data['order'] ?? SidebarCategory::max('order') + 1;

        $category = SidebarCategory::create($data);
        SidebarNavResolver::clearCache();
        return $category;
    }

    public function find($id)
    {
        return SidebarCategory::findOrFail($id);
    }

    public function update($id, array $data)
    {
        $category = $this->find($id);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['order'] = $data['order'] ?? SidebarCategory::max('order') + 1;
        $updated = $category->update($data);
        SidebarNavResolver::clearCache();
        return $updated;
    }

    public function delete($id)
    {
        $category = $this->find($id);
        $deleted = $category->delete();
        SidebarNavResolver::clearCache();
        return $deleted;
    }

    /**
     * @ Base Query
     *
     * Deliberately UNORDERED. Yajra appends its `order[]` clauses to whatever
     * the query already carries, so an ORDER BY here would stay the primary
     * sort and every header click would silently do nothing. The grid asks for
     * `order` ascending by default (its `order:` option); exportRows() adds the
     * same default for the downloads.
     */
    protected function baseQuery(Request $request)
    {
        return SidebarCategory::query();
    }

    public function getDatatable(Request $request)
    {
        return DataTables::of($this->baseQuery($request))
            // editColumn (not addColumn) on the four real DB columns: Yajra then
            // still treats them as sortable/searchable SQL columns and only
            // swaps the rendered value.
            ->editColumn('created_at', fn ($e) =>
                optional($e)->created_at ? optional($e)->created_at->format('d-m-Y') : '-'
            )
            ->editColumn('slug', fn ($e) =>
                '<span class="sbm-slug">'.e($e->slug).'</span>'
            )
            ->editColumn('order', fn ($e) =>
                $e->order === null ? '<span class="sbm-muted">—</span>' : $this->orderBadge($e)
            )
            ->editColumn('icon', fn ($e) => $this->iconBadge($e))
            ->addColumn('status', fn ($e) => $this->statusBadge($e))
            ->addColumn('action', fn ($e) => $this->actionButtons($e))
            ->rawColumns(['action', 'status', 'icon', 'order', 'slug'])
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
            .'<span class="material-symbols-rounded" aria-hidden="true">'.e($icon).'</span></span>';
    }

    /**
     * Status column — a soft badge, display only. The control that changes it is
     * the switch in the Action column (§3b). data-order lets a client-side sort
     * order by state.
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
     * Delete is guarded: an ACTIVE category still drives the live sidebar, so it
     * renders as a disabled span explaining why rather than a red icon that the
     * page would refuse. The switch caption names the ACTION, not the state.
     */
    private function actionButtons($data)
    {
        $isActive = (int) $data->is_active === 1;
        $name = e($data->name);
        $switchId = 'sbmCatStatus'.(int) $data->id;

        $html = '<div class="sbm-act-group" role="group" aria-label="Actions for '.$name.'">';

        $html .= '
            <button type="button" class="sbm-act sbm-act--edit sbm-edit-btn"
                    data-id="'.(int) $data->id.'"
                    data-name="'.$name.'"
                    data-slug="'.e($data->slug).'"
                    data-icon="'.e((string) $data->icon).'"
                    data-order="'.e((string) $data->order).'"
                    data-status="'.($isActive ? '1' : '0').'"
                    title="Edit category" aria-label="Edit category '.$name.'">
                <span class="sbm-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                <span class="sbm-act__label">Edit</span>
            </button>';

        // No .form-check/.form-switch wrapper — that combination yanks the input
        // -2.375rem left (custom.css:107-112) and breaks this layout (§3b trap 1).
        //
        // Two classes, two jobs:
        //   plain-status-toggle           — the design-system TOGGLE skin
        //     (custom.css:41-95): pill track, sliding knob, gold off / green on.
        //     It is the wrapper-less variant, which is exactly this layout. Drop
        //     it and the input falls back to Bootstrap's square checkbox.
        //   sidebar-category-status-toggle — this page's JS hook. Deliberately
        //     NOT `status-toggle`: that one is bound globally by custom.js:170 to
        //     the generic table/column endpoint, and this grid posts to its own
        //     route instead.
        $html .= '
            <label class="sbm-act sbm-act--toggle" for="'.$switchId.'">
                <span class="sbm-act__icon">
                    <input class="form-check-input plain-status-toggle sidebar-category-status-toggle"
                           type="checkbox" role="switch"
                           id="'.$switchId.'"
                           data-table="sidebar_categories" data-column="is_active"
                           data-id="'.(int) $data->id.'" data-name="'.$name.'"
                           '.($isActive ? 'checked' : '').'
                           aria-label="'.($isActive ? 'Deactivate' : 'Activate').' category '.$name.'">
                </span>
                <span class="sbm-act__label">'.($isActive ? 'Deactivate' : 'Activate').'</span>
            </label>';

        if ($isActive) {
            $html .= '
            <span class="sbm-act sbm-act--del is-disabled"
                  title="Deactivate this category before deleting it" aria-disabled="true">
                <span class="sbm-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
                <span class="sbm-act__label">Delete</span>
            </span>';
        } else {
            $html .= '
            <form action="'.route('sidebar.categories.destroy', $data->id).'" method="POST" class="sbm-delete-form">
                '.csrf_field().'
                '.method_field('DELETE').'
                <button type="submit" class="sbm-act sbm-act--del" data-name="'.$name.'"
                        aria-label="Delete category '.$name.'">
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
            ['key' => 'name', 'heading' => 'Name', 'class' => 'sbm-print-name',
                'value' => fn ($row) => (string) $row->name],
            ['key' => 'slug', 'heading' => 'Slug', 'class' => 'sbm-print-slug',
                'value' => fn ($row) => (string) $row->slug],
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
     * The rows the export writes — the same search term the grid is showing, so
     * a download matches what is on screen.
     */
    public function exportRows(Request $request)
    {
        $query = $this->baseQuery($request)->orderBy('order', 'asc');

        $search = trim((string) $request->input('q', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('icon', 'like', $like);
            });
        }

        return $query->get();
    }
}
