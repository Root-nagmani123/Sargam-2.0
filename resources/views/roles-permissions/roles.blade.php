@extends('admin.layouts.master')
@section('title', 'Role & Permission')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Role & Permission" />
    <x-session_message />
    <div class="datatables">
        <div class="card" style="border-left: 4px solid #004a93;">
            <div class="card-body">
                <div class="table-responsive">
                    <div class="row">
                        <div class="col-6">
                            <h4>Role & Permission</h4>
                        </div>
                        <div class="col-6">
                            <div class="d-flex justify-content-end align-items-center gap-2">
                                <a href="#" class="btn btn-primary d-flex align-items-center" onclick="RoleModal()">
                                    <i class="material-icons menu-icon material-symbols-rounded"
                                        style="font-size: 20px; vertical-align: middle;">add</i>
                                    Add Role
                                </a>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <x-data-table.table 
                        :columns="$columns"
                        :filters="[]" 
                        ajax-route="{{route('roles.index')}}" 
                        id="roles-table" 
                    />
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
</script>
@endsection