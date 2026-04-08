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
        return $category;
    }

    public function store(array $data)
    {
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['order'] = $data['order'] ?? SidebarCategory::max('order') + 1;

        return SidebarCategory::create($data);
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
        return $category->update($data);
    }

    public function delete($id)
    {
        $category = $this->find($id);

        return $category->delete(); // soft delete
    }

    public function columns(): array
    {
        return [
            ['title' => 'Sr No.', 'data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
            ['title' => 'Name', 'data' => 'name'],
            ['title' => 'Slug', 'data' => 'slug'],
            ['title' => 'Icon', 'data' => 'icon'],
            ['title' => 'Order', 'data' => 'order'],
            ['title' => 'Created Date', 'data' => 'created_at'],
            ['title' => 'Status', 'data' => 'status','orderable' => false, 'searchable' => false],
            ['title' => 'Action', 'data' => 'action', 'orderable' => false, 'searchable' => false],
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
        return '<span class="badge bg-primary">'.$data->order.'</span>';
    }

    private function iconBadge($data)
    {
        return '<span class="material-symbols-rounded fs-6" aria-hidden="true">'.$data->icon.'</span>';
    }

    private function statusBadge($data)
    {
        $checked = $data->is_active ? 'checked' : '';

        return '
            <div class="form-check form-switch d-inline-block">
                <input 
                    class="form-check-input sidebar-category-status-toggle" 
                    type="checkbox" 
                    role="switch"
                    '.$checked.'
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

        $buttons = '
        <div class="d-inline-flex align-items-center gap-2" role="group" aria-label="Category actions">
            <!-- Edit -->
            <a href="javascript:void(0);" 
            class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 edit-btn" 
            data-item="'.$jsonData.'" 
            aria-label="Edit category">
                <span class="material-symbols-rounded fs-6" aria-hidden="true">edit</span>
                <span class="d-none d-md-inline">Edit</span>
            </a>
        ';

        if ($data->is_active != 1) {
            $buttons .= '
            <form action="'.$deleteUrl.'" method="POST" class="d-inline" 
                onsubmit="return confirm(\'Are you sure you want to delete this record?\');">
                '.csrf_field().'
                '.method_field('DELETE').'
                <button type="submit" 
                        class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1" 
                        aria-label="Delete category">
                    <span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span>
                    <span class="d-none d-md-inline">Delete</span>
                </button>
            </form>
            ';
        }

        $buttons .= '</div>';

        return $buttons;
    }

}