@extends('admin.layouts.master')
@section('title', 'Sidebar Menu Groups')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Sidebar Menu Groups" />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Sidebar Menu Groups</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <a href="#" class="btn btn-primary d-flex align-items-center" onclick="MenuGroupModal()">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 20px; vertical-align: middle;">add</i>
                                    Add Menu Group
                                </a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <x-data-table.table 
                        :columns="$columns"
                        :filters="[]" 
                        ajax-route="{{route('sidebar.menu-groups.index')}}" 
                        id="sidebar-menu-group-table" 
                    />
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="MenuGroupModal" tabindex="-1" aria-labelledby="MenuGroupModalLabel" data-bs-backdrop="static" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="MenuGroupModalLabel">Add / Edit Sidebar Menu Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-3">
                <form id="menuGroupForm" action="" method="POST" >
                    @csrf
                    <div class="form-group mb-2">
                        <label class="form-label" for="category_id">Category <span class="text-danger">*</span></label>
                        <select class="form-select" name="category_id" id="category_id">
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
                    <div class="form-group mb-2">
                        <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="name" placeholder="Enter menu group name" value="{{old('name')}}">
                    </div>
                    <div class="form-group mb-2">
                        <label class="form-label" for="icon">Icon</label>
                        <input type="text" class="form-control" name="icon" id="icon" placeholder="e.g. bi bi-house" value="{{old('icon')}}">
                    </div>
                    <div class="form-group mb-2">
                        <label class="form-label" for="order">Order</label>
                        <input type="number" class="form-control" name="order" id="order" placeholder="0" value="{{old('order')}}">
                    </div>
                    <div class="form-group mb-2">
                        <label class="form-label" for="status">Status</label>
                        <select class="form-select" name="is_active" id="is_active">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-success" id="SubmitMenuGroupForm"><i class="bi bi-save me-2"></i>Save</button>
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
        MenuGroupModal(data);
    })

    function MenuGroupModal(data = null) {
        $('input[name="_method"]').remove();
        if (data) {
            $('#category_id').val(data.category_id);
            $('#name').val(data.name);
            $('#icon').val(data.icon);
            $('#order').val(data.order);
            $('#is_active').val(data.is_active);
            $('#menuGroupForm').attr('action', '/sidebar/menu-groups/' + data.id);
            $('#menuGroupForm').append('<input type="hidden" name="_method" value="PATCH">');
        }else{
            $('#menuGroupForm')[0].reset();
            $('#menuGroupForm').attr('action', '/sidebar/menu-groups');
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


        $("#menuGroupForm").validate({
            ignore: ".ignore",
            rules: {
                category_id: {
                    required: true,
                },
                name: {
                    required: true,
                    minlength: 2,
                    maxlength: 100,
                    nameRegex: true,
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
                }
            },
            messages: {
                category_id: {
                    required: "Please select category",
                },
                name: {
                    required: "Please enter menu group name",
                    minlength: "Name must be at least 2 characters",
                    maxlength: "Name must be less than 100 characters"
                },
                icon: {
                    maxlength: "Icon must be less than 100 characters"
                },
                order: {
                    digits: "Order must be a number"
                },
                is_active: {
                    required: "Please select status"
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
                let btn = $("#SubmitMenuGroupForm");
                btn.prop("disabled", true);
                btn.html('<i class="fa fa-spinner fa-spin"></i> Processing...');
                form.submit();
            }
        });

        $(document).on('change', '.sidebar-menu-group-status-toggle', function () {
            let id = $(this).data('id');
            let value = $(this).is(':checked') ? 1 : 0;
            let column = $(this).data('column');
            
            $.ajax({
                url: "{{ route('sidebar.menu-groups.status', ':id') }}".replace(':id', id),
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
</script>
@endsection