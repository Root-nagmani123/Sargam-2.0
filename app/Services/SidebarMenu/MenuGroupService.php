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
            'columns' => $this->columns(),
            'filters' => $this->filters(),
            'categories' => SidebarCategory::where('is_active', 1)->orderBy('order', 'asc')->get(),
        ];
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

    public function columns(): array
    {
        return [
            ['title' => 'Sr No.', 'data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
            ['title' => 'Category', 'data' => 'category_id'],
            ['title' => 'Name', 'data' => 'name'],
            ['title' => 'Icon', 'data' => 'icon'],
            ['title' => 'Order', 'data' => 'order'],
            ['title' => 'Created Date', 'data' => 'created_at'],
            ['title' => 'Status', 'data' => 'status'],
            ['title' => 'Action', 'data' => 'action', 'orderable' => false, 'searchable' => false],
        ];
    }

    public function filters(): array
    {
        return [
            [
                'name' => 'category_id',
                'label' => 'Category',
                'options' => SidebarCategory::where('is_active', 1)->orderBy('order', 'asc')->get()->map(fn($e) => [$e->id => $e->name]),
            ],
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
        return MenuGroup::query()->with('category')->orderBy('order', 'asc');
    }

    public function getDatatable(Request $request)
    {
        return DataTables::of($this->baseQuery($request))
            ->addColumn('category_id', fn ($e) =>
                optional($e)->category ? optional($e)->category->name : '-'
            )
            ->addColumn('created_at', fn ($e) =>
                optional($e)->created_at ? optional($e)->created_at->format('d-m-Y') : '-'
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
            ->editColumn('status', fn ($e) =>
                $this->statusBadge($e)
            )
            ->rawColumns(['action','icon','order','status'])
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

    private function actionButtons($data)
    {
        $deleteUrl = route('sidebar.menu-groups.destroy', $data->id);
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
    private function statusBadge($data)
    {
        $checked = $data->is_active ? 'checked' : '';

        return '
            <div class="form-check form-switch d-inline-block">
                <input 
                    class="form-check-input sidebar-menu-group-status-toggle" 
                    type="checkbox" 
                    role="switch"
                    '.$checked.'
                    data-id="'.$data->id.'"
                    data-table="sidebar_menu_groups"
                    data-column="is_active"
                >
            </div>
        ';
    }
}