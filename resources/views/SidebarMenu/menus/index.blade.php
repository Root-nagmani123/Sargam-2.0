@extends('admin.layouts.master')
@section('title', 'Sidebar Menus')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Sidebar Menus" />
    <x-session_message />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Sidebar Menus</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <a href="#" class="btn btn-primary d-flex align-items-center" onclick="MenuGroupModal()">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 20px; vertical-align: middle;">add</i>
                                    Add Menu
                                </a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <x-data-table.table 
                        :columns="$columns"
                        :filters="[]" 
                        ajax-route="{{route('sidebar.menus.index')}}" 
                        id="sidebar-menu-table" 
                    />
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="MenuGroupModal" tabindex="-1" aria-labelledby="MenuGroupModalLabel" data-bs-backdrop="static" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="MenuGroupModalLabel">Add / Edit Sidebar Menu Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <form id="menuForm" action="" method="POST" >
                    @csrf
                    <input type="hidden" name="id" id="menuId">
                    <div class="row">
                        <div class="col-6 form-group mb-2">
                                <label class="form-label" for="category_id">Category <span class="text-danger">*</span></label>
                                <select class="form-select" name="category_id" id="category_id" onchange="getGroups(this.value)">
                                    <option value="">Select Category</option>
                                    @if(isset($categories) && $categories->count() > 0)
                                    @forelse($categories as $category)
                                        <option value="{{$category->id}}">{{$category->name}}</option>
                                    @empty
                                        <option value="">No Category Found</option>
                                    @endforelse
                                @else
                                    <option value="">No Category Found</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-6 form-group mb-2">
                                <label class="form-label" for="group_id">Group <span class="text-danger">*</span></label>
                                <select class="form-select sidebar-group-select" name="group_id" id="group_id" onchange="getMenus(this.value)">
                                    <option value="">Select Group</option>
                                    @if(isset($groups) && $groups->count() > 0)
                                    @forelse($groups as $group)
                                        <option value="{{$group->id}}">{{$group->name}}</option>
                                    @empty
                                        <option value="">No Group Found</option>
                                    @endforelse
                                @else
                                    <option value="">No Group Found</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-12 form-group mb-2">
                            <label class="form-label" for="parent_id">Parent Menu </label><br>
                            <small class="text-muted fs-2">(If you select this, this menu will be a sub-menu of the selected parent menu)</small>
                            <select class="form-select sidebar-menu-select" name="parent_id" id="parent_id">
                                <option value="">Select Parent Menu</option>
                                @if(isset($menus) && $menus->count() > 0)
                                    @forelse($menus as $menu)
                                        <option value="{{$menu->id}}">{{$menu->name}}</option>
                                    @empty
                                        <option value="">No Menu Found</option>
                                    @endforelse
                                @else
                                    <option value="">No Menu Found</option>
                                @endif
                            </select>
                        </div>
                        <div class="col-12 form-group mb-2">
                            <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="name" placeholder="Enter menu name" value="{{old('name')}}">
                        </div>
                        <div class="col-12 form-group mb-2">
                            <label class="form-label" for="route">Url </label>
                            <small class="text-muted fs-2">(If you select parent menu, leave this empty)</small>
                            <input type="text" class="form-control" name="route" id="route" placeholder="Enter menu url" value="{{old('route')}}">
                        </div>
                        <div class="col-12 form-group mb-2">
                            <label class="form-label" for="permission_name">Permission Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="permission_name" id="permission_name" placeholder="Enter menu permission name" value="{{old('permission_name')}}">
                        </div>
                        <div class="col-6 form-group mb-2">
                            <label class="form-label" for="icon">Icon</label>
                            <input type="text" class="form-control" name="icon" id="icon" placeholder="e.g. bi bi-house" value="{{old('icon')}}">
                        </div>
                        <div class="col-6 form-group mb-2">
                            <label class="form-label" for="order">Order</label>
                            <input type="number" class="form-control" name="order" id="order" placeholder="0" value="{{old('order')}}">
                        </div>
                        <div class="col-6 form-group mb-2">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-select" name="is_active" id="is_active">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-6 form-group mb-2">
                            <label class="form-label" for="target">Tab</label>
                            <select class="form-select" name="target" id="target">
                                <option value="0" selected>Same Tab</option>
                                <option value="1">New Tab</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-success" id="SubmitMenuForm"><i class="bi bi-save me-2"></i>Save</button>
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
    let manualEdit = false;

    $('#permission_name').on('keyup', function () {
        manualEdit = true;
    });

    $('#name').on('keyup', function () {

        if (manualEdit) return;

        let name = $(this).val();

        let permission = name
            .toLowerCase()
            .trim()
            .replace(/-/g, '_')          
            .replace(/[^a-z0-9_\s]/g, '')   
            .replace(/\s+/g, '_');    

        $('#permission_name').val(permission);
    });

    $(document).on('click', '.edit-btn', function () {
        let data = $(this).data('item');
        MenuGroupModal(data);
    })

    function MenuGroupModal(data = null) {
        if (data) {
            $('#menuId').val(data.id);
            $('#category_id').val(data.category_id);
            $('#group_id').val(data.group_id);
            $('#parent_id').val(data.parent_id);
            $('#name').val(data.name);
            $('#route').val(data.route);
            $('#permission_name').val(data.permission_name);
            $('#icon').val(data.icon);
            $('#order').val(data.order);
            $('#is_active').val(data.is_active);
            $('#target').val(data.target);
            $('#menuForm').attr('action', '/sidebar/menus/' + data.id);
            $('#menuForm').append('<input type="hidden" name="_method" value="PUT">');
        }else{
            $('#menuForm')[0].reset();
            $('#menuId').val('');
            $('#menuForm').attr('action', '/sidebar/menus');
            $('input[name="_method"]').remove();
        }
        $('#MenuGroupModal').modal('show');
    }

    $(document).ready(function () {
        $.validator.addMethod("nameRegex", function(value, element) {
            return this.optional(element) || /^[A-Za-z .'-]+$/.test(value);
        }, "Name can only contain letters, spaces, ., ' and -.");

        // Slug validation (only lowercase, dash)
        $.validator.addMethod("slugRegex", function(value, element) {
            return this.optional(element) || /^[a-z0-9-]+$/.test(value);
        }, "Slug can only contain lowercase letters, numbers and hyphens.");


        $("#menuForm").validate({
            ignore: ".ignore",
            rules: {
                group_id: {
                    required: true,
                },
                name: {
                    required: true,
                    minlength: 2,
                    maxlength: 100,
                    // nameRegex: true,
                },
                route: {
                    required: false,
                    maxlength: 100,
                },
                permission_name: {
                    required: false,
                    minlength: 2,
                    maxlength: 100,
                },
                icon: {
                    maxlength: 100
                },
                order: {
                    required: false,
                    digits: true
                },
                is_active: {
                    required: true
                },
                target: {
                    required: true
                }
            },
            messages: {
                group_id: {
                    required: "Please select group",
                },
                name: {
                    required: "Please enter menu name",
                    minlength: "Name must be at least 2 characters",
                    maxlength: "Name must be less than 100 characters"
                },
                route: {
                    required: "Route is required",
                    maxlength: "Route must be less than 100 characters"
                },
                permission_name: {
                    required: "Permission name is required",
                    minlength: "Permission name must be at least 2 characters",
                    maxlength: "Permission name must be less than 100 characters"
                },
                icon: {
                    maxlength: "Icon must be less than 100 characters"
                },
                order: {
                    required: "Order is required",
                    digits: "Order must be a number"
                },
                is_active: {
                    required: "Please select status"
                },
                target: {
                    required: "Please select tab"
                }
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
                let btn = $("#SubmitMenuForm");
                btn.prop("disabled", true);
                btn.html('<i class="fa fa-spinner fa-spin"></i> Processing...');
                form.submit();
            }
        });

        $(document).on('change', '.sidebar-menu-status-toggle', function () {
            let id = $(this).data('id');
            let value = $(this).is(':checked') ? 1 : 0;
            let column = $(this).data('column');
            
            $.ajax({
                url: "{{ route('sidebar.menus.status', ':id') }}".replace(':id', id),
                type: "GET",
                data: {
                    _token: "{{ csrf_token() }}",
                    is_active: value
                },
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function (xhr) {
                    toastr.error('Something went wrong');
                }
            });
        });
    });

    function getGroups(category_id)
    {
        if(category_id == ''){
            $('.sidebar-group-select').empty();
            $('.sidebar-group-select').append('<option value="">Select Group</option>');
            return;
        }
        $.ajax({
            url: "{{ route('sidebar.getGroups', ':category_id') }}".replace(':category_id', category_id),
            type: "GET",
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function (response) {
                $('.sidebar-group-select').empty();
                if (response.success) {
                    $('.sidebar-group-select').append('<option value="">Select Group</option>');
                    response.groups.forEach(function (group) {
                        $('.sidebar-group-select').append('<option value="' + group.id + '">' + group.name + '</option>');
                    });
                } else {
                   $('.sidebar-group-select').append('<option value="">No Group Found</option>');
                }
            },
            error: function (xhr) {
                toastr.error('Something went wrong');
            }
        });
    }

    function getMenus(group_id)
    {
        if(group_id == ''){
            $('#parent_id').empty();
            $('#parent_id').append('<option value="">Select Parent Menu</option>');
            return;
        }
        $.ajax({
            url: "{{ route('sidebar.getMenus', ':group_id') }}".replace(':group_id', group_id),
            type: "GET",
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function (response) {
                $('.sidebar-menu-select').empty();
                if (response.success) {
                    $('.sidebar-menu-select').append('<option value="">Select Menu</option>');
                    response.menus.forEach(function (menu) {
                        $('.sidebar-menu-select').append('<option value="' + menu.id + '">' + menu.name + '</option>');
                    });
                } else {
                    $('.sidebar-menu-select').append('<option value="">No Menu Found</option>');
                }
            },
            error: function (xhr) {
                toastr.error('Something went wrong');
            }
        });
    }

    $(document).on('change', '.sidebar-menu-status-toggle', function () {
        let id = $(this).data('id');
        let value = $(this).is(':checked') ? 1 : 0;
        let column = $(this).data('column');
        
        $.ajax({
            url: "{{ route('sidebar.menus.status', ':id') }}".replace(':id', id),
            type: "GET",
            data: {
                _token: "{{ csrf_token() }}",
                is_active: value
            },
            success: function (response) {
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
                $('#sidebar-menu-table').DataTable().ajax.reload();
            },
            error: function (xhr) {
                toastr.error('Something went wrong');
            }
        });
    });
</script>
@endsection