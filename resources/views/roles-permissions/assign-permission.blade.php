@extends('admin.layouts.master')
@section('title', 'Assign Permission')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Assign Permission" />
    <x-session_message />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Assign Permission ({{ ucfirst($role->name) }})</h4>
                <div class="dropdown">
                    <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" id="columnToggleBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        <i class="bi bi-layout-three-columns me-1"></i> Columns
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end p-2" aria-labelledby="columnToggleBtn">
                        <li class="dropdown-item-text fw-semibold border-bottom pb-1 mb-1">Show/Hide Columns</li>
                        <li><label class="dropdown-item"><input type="checkbox" class="form-check-input me-2 col-toggle" data-col="0" checked> Category</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="form-check-input me-2 col-toggle" data-col="1" checked> Group</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="form-check-input me-2 col-toggle" data-col="2" checked> Menu</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="form-check-input me-2 col-toggle" data-col="3" checked> Sub Menu</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="form-check-input me-2 col-toggle" data-col="4" checked> Permission</label></li>
                        <li><label class="dropdown-item"><input type="checkbox" class="form-check-input me-2 col-toggle" data-col="5" checked> Action</label></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <!-- Search & Filter Bar -->
                <div class="row g-2 mb-3">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="permissionSearch" class="form-control" placeholder="Search by menu, permission...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select id="categoryFilter" class="form-select">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->name }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="statusFilter" class="form-select">
                            <option value="">All Status</option>
                            <option value="enabled">Enabled</option>
                            <option value="disabled">Disabled</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-outline-secondary w-100" id="clearFilters" title="Clear filters">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                <div id="filterResultCount" class="text-muted small mb-2"></div>

                <div class="table-responsive">
                    <table class="table" id="permissionTable">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Group</th>
                                <th>Menu</th>
                                <th>Sub Menu</th>
                                <th>Permission</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($categories as $category)
                            @foreach($category->groups as $group)
                                @foreach($group->menus as $menu)
                                    @if($menu->children->count() > 0)
                                        @foreach($menu->children as $child)
                                            <tr data-category="{{ $category->name }}"
                                                data-group="{{ $group->name }}"
                                                data-menu="{{ $menu->name }}"
                                                data-submenu="{{ $child->name }}"
                                                data-permission="{{ $child->permission_name }}">
                                                <td>{{ $category->name }}</td>
                                                <td>{{ $group->name }}</td>
                                                <td>{{ $menu->name }}</td>
                                                <td class="ps-4">{{ $child->name }}</td>
                                                <td>{{ $child->permission_name }}</td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input permission-toggle" type="checkbox"
                                                            name="permissions[]"
                                                            data-id="{{ $child->id }}"
                                                            value="{{ $child->permission_name }}"
                                                            {{ in_array($child->permission_name, $rolePermissions) ? 'checked' : '' }}>
                                                        <label class="form-check-label"></label>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr data-category="{{ $category->name }}"
                                            data-group="{{ $group->name }}"
                                            data-menu="{{ $menu->name }}"
                                            data-submenu="-"
                                            data-permission="{{ $menu->permission_name }}">
                                            <td>{{ $category->name }}</td>
                                            <td>{{ $group->name }}</td>
                                            <td>{{ $menu->name }}</td>
                                            <td>-</td>
                                            <td>{{ $menu->permission_name }}</td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input permission-toggle" type="checkbox"
                                                        name="permissions[]"
                                                        data-id="{{ $menu->id }}"
                                                        value="{{ $menu->permission_name }}"
                                                        {{ in_array($menu->permission_name, $rolePermissions) ? 'checked' : '' }}>
                                                    <label class="form-check-label"></label>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
                    <div id="noResultsMsg" class="text-center text-muted py-4 d-none">
                        <i class="bi bi-search fs-4 d-block mb-2"></i>No permissions found matching your filters.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="RoleModal" tabindex="-1" aria-labelledby="MenuGroupModalLabel" data-bs-backdrop="static" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="MenuGroupModalLabel">Add / Edit Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <form id="roleForm" action="" method="POST" >
                    @csrf
                    <input type="hidden" name="id" id="roleId">
                    <div class="form-group mb-2">
                        <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="name" placeholder="Enter role name" value="{{old('name')}}">
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-success" id="SubmitRoleForm"><i class="bi bi-save me-2"></i>Save</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle me-2"></i>Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script>
    $(document).on('click', '.edit-btn', function () {
        let data = $(this).data('item');
        RoleModal(data);
    })

    function RoleModal(data = null) {
        if (data) {
            $('#roleId').val(data.id);
            $('#name').val(data.name);
            $('#roleForm').attr('action', '/roles/' + data.id);
            $('#roleForm').append('<input type="hidden" name="_method" value="PUT">');
        }else{
            $('#roleForm')[0].reset();
            $('#roleId').val('');
            $('#roleForm').attr('action', '/roles');
            $('input[name="_method"]').remove();
        }
        $('#RoleModal').modal('show');
    }

    $(document).ready(function () {
        $.validator.addMethod("nameRegex", function(value, element) {
            return this.optional(element) || /^[A-Za-z .'-]+$/.test(value);
        }, "Name can only contain letters, spaces, ., ' and -.");

        // Slug validation (only lowercase, dash)
        $.validator.addMethod("slugRegex", function(value, element) {
            return this.optional(element) || /^[a-z0-9-]+$/.test(value);
        }, "Slug can only contain lowercase letters, numbers and hyphens.");


        $("#roleForm").validate({
            ignore: ".ignore",
            rules: {
                name: {
                    required: true,
                    minlength: 2,
                    maxlength: 100,
                    nameRegex: true,
                },
            },
            messages: {
                name: {
                    required: "Please enter role name",
                    minlength: "Name must be at least 2 characters",
                    maxlength: "Name must be less than 100 characters"
                },
               
            },
            errorClass: "is-invalid",
            validClass: "is-valid",
            errorElement: "div",
            highlight: function (element) {
                $(element).addClass("is-invalid").removeClass("is-valid");
            },
            unhighlight: function (element) {
                $(element).removeClass("is-invalid").addClass("is-valid");
            },
            errorPlacement: function (error, element) {
                error.addClass("invalid-feedback");
                element.closest(".form-group").append(error);
            },
            submitHandler: function (form) {
                let btn = $("#SubmitRoleForm");
                btn.prop("disabled", true);
                btn.html('<i class="fa fa-spinner fa-spin"></i> Processing...');
                form.submit();
            }
        });
    });

    // Search & Filter
    function applyPermissionFilters() {
        var search = $('#permissionSearch').val().toLowerCase().trim();
        var category = $('#categoryFilter').val().toLowerCase();
        var status = $('#statusFilter').val();
        var rows = $('#permissionTable tbody tr');
        var visible = 0;

        rows.each(function () {
            var $row = $(this);
            var matchSearch = !search ||
                ($row.data('menu') + ' ' + $row.data('submenu') + ' ' + $row.data('permission') + ' ' + $row.data('group'))
                    .toLowerCase().indexOf(search) > -1;
            var matchCategory = !category || $row.data('category').toLowerCase() === category;
            var isChecked = $row.find('.permission-toggle').is(':checked');
            var matchStatus = !status ||
                (status === 'enabled' && isChecked) ||
                (status === 'disabled' && !isChecked);

            if (matchSearch && matchCategory && matchStatus) {
                $row.show();
                visible++;
            } else {
                $row.hide();
            }
        });

        var total = rows.length;
        $('#filterResultCount').text(visible === total ? 'Showing all ' + total + ' permissions' : 'Showing ' + visible + ' of ' + total + ' permissions');
        $('#noResultsMsg').toggleClass('d-none', visible > 0);
    }

    $('#permissionSearch').on('input', applyPermissionFilters);
    $('#categoryFilter, #statusFilter').on('change', applyPermissionFilters);
    $('#clearFilters').on('click', function () {
        $('#permissionSearch').val('');
        $('#categoryFilter').val('');
        $('#statusFilter').val('');
        applyPermissionFilters();
    });

    // Init count on load
    $(document).ready(function () { applyPermissionFilters(); });

    // Column hide/unhide toggle
    $(document).on('change', '.col-toggle', function () {
        let colIndex = $(this).data('col');
        let isVisible = $(this).is(':checked');
        $('#permissionTable th:nth-child(' + (colIndex + 1) + '), #permissionTable td:nth-child(' + (colIndex + 1) + ')').toggle(isVisible);
    });

    $(document).on('change', '.permission-toggle', function () {
        let menuId = $(this).data('id');
        let permission = $(this).val();
        let isChecked = $(this).is(':checked') ? 1 : 0;
        $.ajax({
            url: "{{ route('assign.roles.permissions', $role->id) }}",
            type: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                menu_id: menuId,
                permission: permission,
                status: isChecked
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                };
            },
            error: function (err) {
                toastr.error('Something went wrong');
            }
        });
    });
</script>

@endsection