@extends('admin.layouts.master')
@section('title', 'Assign Permission')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Assign Permission" />
    <x-session_message />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
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
                        @php
                            $lastCategory = null;
                            $lastGroup = null;
                        @endphp

                        @foreach($categories as $category)
                            @foreach($category->groups as $group)
                                @foreach($group->menus as $menu)
                                    @if($menu->children->count() > 0)
                                        @php $firstChild = true; @endphp
                                        @foreach($menu->children as $child)
                                            <tr>
                                                {{-- CATEGORY --}}
                                                <td>
                                                    @if($lastCategory != $category->name)
                                                        {{ $category->name }}
                                                        @php $lastCategory = $category->name; @endphp
                                                    @endif
                                                </td>

                                                {{-- GROUP --}}
                                                <td>
                                                    @if($lastGroup != $group->name)
                                                        {{ $group->name }}
                                                        @php $lastGroup = $group->name; @endphp
                                                    @endif
                                                </td>

                                                {{-- MENU (ONLY FIRST ROW) --}}
                                                <td>
                                                    @if($firstChild)
                                                        {{ $menu->name }}
                                                        @php $firstChild = false; @endphp
                                                    @endif
                                                </td>

                                                {{-- SUB MENU --}}
                                                <td class="ps-4"> {{ $child->name }}</td>

                                                {{-- PERMISSION --}}
                                                <td>{{ $child->permission_name }}</td>

                                                {{-- ACTION --}}
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input permission-toggle" type="checkbox"
                                                            name="permissions[]"    
                                                            data-id="{{ $child->id }}"
                                                            value="{{ $child->permission_name }}"
                                                            {{ in_array($child->permission_name, $rolePermissions) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="flexSwitchCheckDefault"></label>
                                                    </div>
                                                </td>

                                            </tr>
                                        @endforeach
                                    @else
                                        {{-- NO SUBMENU → NORMAL ROW --}}
                                        <tr>
                                            <td>{{ $category->name }}</td>
                                            <td>{{ $group->name }}</td>
                                            <td>{{ $menu->name }}</td>
                                            <td>-</td>
                                            <td>{{ $menu->permission_name }}</td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input permission-toggle" type="checkbox"
                                                        name="permissions[]"
                                                        data-id="{{$menu->id }}"
                                                        value="{{ $menu->permission_name }}"
                                                        {{ in_array($menu->permission_name, $rolePermissions) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="flexSwitchCheckDefault"></label>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
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