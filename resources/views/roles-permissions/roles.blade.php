@extends('admin.layouts.master')
@section('title', 'Role & Permission')
@push('styles')
<style>
/* =====================================================================
   Role & Permission — page-scoped polish.
   Tokens come from sargam-app.css (--ds-*). Scoped to .roles-perm-page /
   the modal id so nothing leaks to other pages.
   ===================================================================== */
.roles-perm-page .btn-primary { border-radius: var(--ds-radius-1); font-weight: 600; }

/* Neutral uppercase table header (matches the modernized index pages) */
.roles-perm-page #roles-table thead th {
    background: var(--ds-surface-2) !important;
    color: var(--ds-ink-muted) !important;
    font-size: 0.8125rem;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    font-weight: 600;
    border-bottom: 1px solid var(--ds-line);
    white-space: nowrap;
}
.roles-perm-page #roles-table tbody td {
    font-size: 0.9rem;
    color: var(--ds-ink);
    vertical-align: middle;
}

/* Modal shell + form */
.roles-perm-modal .modal-content { border: 0; border-radius: var(--ds-radius-2); box-shadow: 0 10px 40px rgba(16, 24, 40, .18); }
.roles-perm-modal .modal-header { border-bottom: 1px solid var(--ds-line); padding: var(--ds-space-4); }
.roles-perm-modal .modal-body { padding: var(--ds-space-4); }
.roles-perm-modal .form-label { font-size: 0.8125rem; font-weight: 600; color: var(--ds-ink); margin-bottom: 0.35rem; }
.roles-perm-modal .form-control { border-radius: var(--ds-radius-1); font-size: 0.9rem; }
.roles-perm-modal .form-control:focus { border-color: #86b7fe; box-shadow: var(--ds-focus-ring); }
.roles-perm-modal .im-form-footer {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: var(--ds-space-2);
    margin-top: var(--ds-space-5);
    padding-top: var(--ds-space-4);
    border-top: 1px solid var(--ds-line);
}
.roles-perm-modal .im-form-footer .btn { border-radius: var(--ds-radius-1); font-weight: 600; }
</style>
@endpush
@section('setup_content')
<div class="container-fluid roles-perm-page">
    <x-breadcrum title="Role & Permission">
        <a onclick="RoleModal()"
            class="btn btn-primary d-inline-flex align-items-center justify-content-center gap-2 fw-semibold text-nowrap">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Role &amp; Permission</span>
        </a>
    </x-breadcrum>
    <x-session_message />
    <div class="datatables">
        <div class="ds-card">
            <div class="ds-card-body p-3 p-md-4">
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnRolesColumns"
                        data-bs-toggle="modal" data-bs-target="#rolesColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="table-responsive">
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

<div class="modal fade roles-perm-modal" id="RoleModal" tabindex="-1" aria-labelledby="MenuGroupModalLabel" data-bs-backdrop="static" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="MenuGroupModalLabel">Add / Edit Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="roleForm" action="" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="roleId">
                    <div class="form-group mb-2">
                        <label class="form-label" for="name">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="name" placeholder="Enter role name" value="{{old('name')}}">
                    </div>
                    <div class="im-form-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle me-2"></i>Cancel</button>
                        <button type="submit" class="btn btn-primary px-4" id="SubmitRoleForm"><i class="bi bi-save me-2"></i>Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade roles-perm-modal" id="rolesColumnVisibilityModal" tabindex="-1" aria-labelledby="rolesColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="rolesColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3" id="rolesColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
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

    /* ---- Column Visibility (drives the live DataTable via its API) ----
       Page-scoped: reads the columns from the #roles-table DataTable the
       shared data-table component initializes, and toggles them via the
       .column().visible() API. Persisted to localStorage. */
    (function () {
        var TABLE = '#roles-table';
        var storageKey = 'rolesPermission:hiddenColumns:v1';

        function getHidden() {
            try {
                var raw = localStorage.getItem(storageKey);
                var arr = raw ? JSON.parse(raw) : [];
                return Array.isArray(arr) ? arr : [];
            } catch (e) { return []; }
        }
        function persist(arr) {
            try { localStorage.setItem(storageKey, JSON.stringify(arr)); } catch (e) {}
        }

        function setupRolesColumns(dt) {
            if (!dt) { return; }
            var hidden = getHidden();

            dt.columns().every(function () {
                var idx = this.index();
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();

            var $grid = $('#rolesColumnToggleGrid');
            if (!$grid.length) { return; }
            $grid.empty();

            dt.columns().every(function () {
                var idx = this.index();
                var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) { return; }

                var inputId = 'rolescolvis_' + idx;
                var $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                var $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId);
                var $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', hidden.indexOf(idx) === -1);

                $cb.on('change', function () {
                    var h = getHidden();
                    var pos = h.indexOf(idx);
                    if (this.checked) {
                        if (pos !== -1) h.splice(pos, 1);
                    } else {
                        if (pos === -1) h.push(idx);
                    }
                    persist(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                });

                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }

        (function whenReady(tries) {
            tries = tries || 0;
            if ($.fn.DataTable && $.fn.DataTable.isDataTable(TABLE)) {
                setupRolesColumns($(TABLE).DataTable());
            } else if (tries < 100) {
                setTimeout(function () { whenReady(tries + 1); }, 100);
            }
        })(0);
    })();
</script>
@endsection