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

    public function pageData(): array
    {
        return [
            'columns' => $this->columns(),
            'filters' => $this->filters(),
        ];
    }

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

    public function columns(): array
    {
        return [
            ['title' => 'Sr No.', 'data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false, 'className' => 'text-center text-secondary'],
            ['title' => 'Name', 'data' => 'name', 'className' => 'fw-medium'],
            ['title' => 'Slug', 'data' => 'slug', 'className' => 'font-monospace small'],
            ['title' => 'Icon', 'data' => 'icon', 'className' => 'text-center'],
            ['title' => 'Order', 'data' => 'order', 'className' => 'text-center'],
            ['title' => 'Created', 'data' => 'created_at', 'className' => 'text-nowrap text-secondary small'],
            ['title' => 'Status', 'data' => 'status', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ['title' => 'Action', 'data' => 'action', 'orderable' => false, 'searchable' => false, 'className' => 'text-end'],
        ];
    }

    public function filters(): array
    {
        return [
            [
                'name' => 'role',
                'label' => 'Role',
                'options' => [
                    'admin' => 'Admin',
                    'manager' => 'Manager',
                    'user' => 'User',
                ],
            ],
            [
                'name' => 'status',
                'label' => 'Status',
                'options' => [
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ],
            ],
        ];
    }

    # @ Base Query
    protected function baseQuery(Request $request)
    {
        return SidebarCategory::orderBy('order', 'asc');
    }

    public function getDatatable(Request $request)
    {
        return DataTables::of($this->baseQuery($request))
            ->addColumn('created_at', fn ($e) =>
                optional($e)->created_at ? optional($e)->created_at->format('d-m-Y') : '-'
            )
            ->editColumn('status', fn ($e) =>
                $this->statusBadge($e)
            )
            ->addColumn('action', fn ($e) =>
                $this->actionButtons($e)
            )
            ->addColumn('order', fn ($e) =>
                $e->order == null ? '-' : $this->orderBadge($e)
            )
            ->addColumn('icon', fn ($e) =>
                $e->icon == null ? '-' : $this->iconBadge($e)
            )
            ->rawColumns(['action','status','icon','order'])
            ->addIndexColumn()
            ->make(true);
    }

    private function orderBadge($data)
    {
        return '<span class="badge rounded-pill text-bg-primary px-3 py-2 fw-medium">'.e($data->order).'</span>';
    }

    private function iconBadge($data)
    {
        if ($data->icon === null || $data->icon === '') {
            return '<span class="text-secondary user-select-none">—</span>';
        }

        $icon = trim($data->icon);
        if (str_contains($icon, 'bi-') || str_starts_with($icon, 'bi ')) {
            $iconClass = str_contains($icon, 'bi ') ? $icon : 'bi '.$icon;

            return '<span class="d-inline-flex align-items-center justify-content-center rounded-2 border bg-light px-2 py-1" title="'.e($icon).'">'
                .'<i class="'.e($iconClass).' fs-5 text-primary" aria-hidden="true"></i></span>';
        }

        return '<span class="material-symbols-rounded fs-5 align-middle text-primary" aria-hidden="true">'.e($icon).'</span>';
    }

    private function statusBadge($data)
    {
        $checked = $data->is_active ? 'checked' : '';
        $ariaChecked = $data->is_active ? 'true' : 'false';
        $switchId = 'sidebar-cat-status-'.(int) $data->id;
        $label = 'Toggle active status for category '.e($data->name);

        return '
            <div class="form-check form-switch d-flex justify-content-center mb-0">
                <input
                    class="form-check-input sidebar-category-status-toggle gigw-switch-touch"
                    type="checkbox"
                    role="switch"
                    id="'.$switchId.'"
                    '.$checked.'
                    aria-label="'.$label.'"
                    aria-checked="'.$ariaChecked.'"
                    data-id="'.$data->id.'"
                    data-table="sidebar_categories"
                    data-column="is_active"
                >
            </div>
        ';
    }



    private function actionButtons($data)
    {
        $deleteUrl = route('sidebar.categories.destroy', $data->id);
        $jsonData = htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');
        $nameQuoted = e($data->name);

        $buttons = '
        <div class="d-inline-flex align-items-center justify-content-end flex-wrap gap-2" role="group" aria-label="Actions for '.$nameQuoted.'">
            <button type="button"
                class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center edit-btn border-0 bg-transparent text-primary"
                data-item="'.$jsonData.'"
                title="Edit category"
                aria-label="Edit category '.$nameQuoted.'">
                <i class="material-icons material-symbols-rounded fs-6" aria-hidden="true">edit</i>
            </button>
        ';

        if ($data->is_active != 1) {
            $buttons .= '
            <form action="'.$deleteUrl.'" method="POST" class="d-inline"
                onsubmit="return confirm(\'Are you sure you want to delete this record?\');">
                '.csrf_field().'
                '.method_field('DELETE').'
                <button type="submit"
                        class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center delete-btn border-0 bg-transparent"
                        aria-label="Delete category '.$nameQuoted.'">
                    <i class="material-icons material-symbols-rounded fs-6" aria-hidden="true">delete</i>
                </button>
            </form>
            ';
        }

        $buttons .= '</div>';

        return $buttons;
    }

}