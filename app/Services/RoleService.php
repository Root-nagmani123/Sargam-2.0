<?php

######################################
// DEVELOPER INFO 
// => MANJEET CHNAD (शैतान 💀)
// => manjeetchand01@gmail.com
// => +919997294527
// => 17 Mar 2026
######################################

namespace App\Services;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function pageData(): array
    {
        return [
            'columns' => $this->columns(),
            'filters' => $this->filters(),
        ];
    }

    public function permissionPageData(): array
    {
        return [
            'columns' => $this->permissionColumns(),
        ];
    }
    
    
    public function getAll()
    {
        return Role::latest()->get();
    }

    public function getForDropdown()
    {
        return Role::pluck('name', 'id');
    }

    public function store(array $data)
    {
        return Role::create($data);
    }

    public function update($id, array $data)
    {
        $menu = $this->find($id);
        return $menu->update($data);
    }

    public function delete($id)
    {
        $menu = $this->find($id);
        return $menu->delete();
    }

    public function columns(): array
    {
        return [
            ['title' => 'Sr No.', 'data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
            ['title' => 'Role', 'data' => 'name'],
            ['title' => 'Created Date', 'data' => 'created_at'],
            ['title' => 'Action', 'data' => 'action', 'orderable' => false, 'searchable' => false],
        ];
    }
    public function permissionColumns(): array
    {
        return [
            ['title' => 'Sr No.', 'data' => 'DT_RowIndex', 'orderable' => false, 'searchable' => false],
            ['title' => 'Permission', 'data' => 'name'],
            ['title' => 'Action', 'data' => 'action', 'orderable' => false, 'searchable' => false],
        ];
    }

    public function filters(): array
    {
        return [
            [
                'name' => 'category_id',
                'label' => 'Category',
                'options' => null,
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
        return Role::query();
    }

    public function getDatatable(Request $request)
    {
        return DataTables::of($this->baseQuery($request))
            ->addColumn('created_at', fn ($e) =>
                optional($e)->created_at ? optional($e)->created_at->format('d-m-Y') : '-'
            )
            ->addColumn('action', fn ($e) =>
                $this->actionButtons($e)
            )
            ->rawColumns(['action','status'])
            ->addIndexColumn()
            ->make(true);
    }

    private function actionButtons($data)
    {
        $deleteUrl = route('sidebar.menus.destroy', $data->id);
        $jsonData = htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');
        return '
        <div class="d-inline-flex align-items-center gap-2" role="group" aria-label="Menu actions">
            <!-- Assign Permission -->
            <a href="'.route('roles.show', $data->id).'" class="btn btn-sm btn-outline-success d-flex align-items-center gap-1" aria-label="Edit menu">
                <span class="material-symbols-rounded fs-6" aria-hidden="true">lock</span>
                <span class="d-none d-md-inline">Assign Permission</span>
            </a>
            <!-- Edit -->
            <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1 edit-btn" data-item="'.$jsonData.'" aria-label="Edit menu">
                <span class="material-symbols-rounded fs-6" aria-hidden="true">edit</span>
                <span class="d-none d-md-inline">Edit</span>
            </a>
        </div>';
    }
}


//  <!-- Delete --> 
// <form action="'.$deleteUrl.'" method="POST" class="d-inline" onsubmit="return confirm(\'Are you sure you want to delete this record?\');">
//     '.csrf_field().'
//     '.method_field('DELETE').'
//     <button type="submit" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1" aria-label="Delete category">
//         <span class="material-symbols-rounded fs-6" aria-hidden="true">delete</span>
//         <span class="d-none d-md-inline">Delete</span>
//     </button>
// </form>